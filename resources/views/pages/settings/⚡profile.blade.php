<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    
    public string $bank_name = '';
    public string $bank_account_name = '';
    public string $bank_account_number = '';
    public string $bank_routing = '';
    
    public string $mobile_bank_name = '';
    public string $mobile_bank_number = '';
    public string $mobile_bank_type = '';
    
    public $avatar;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        
        $bankDetails = Auth::user()->bank_account_details ?? [];
        $this->bank_name = $bankDetails['name'] ?? '';
        $this->bank_account_name = $bankDetails['account_name'] ?? '';
        $this->bank_account_number = $bankDetails['account_number'] ?? '';
        $this->bank_routing = $bankDetails['routing'] ?? '';
        
        $mobileDetails = Auth::user()->mobile_banking_details ?? [];
        $this->mobile_bank_name = $mobileDetails['name'] ?? '';
        $this->mobile_bank_number = $mobileDetails['number'] ?? '';
        $this->mobile_bank_type = $mobileDetails['type'] ?? '';
    }

    public function updatedAvatar()
    {
        $this->validate([
            'avatar' => 'image|max:2048',
        ]);

        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $this->avatar->store('avatars', 'public');

        $user->update([
            'avatar' => $path,
        ]);

        $this->reset('avatar');

        Flux::toast(variant: 'success', text: __('Profile photo updated.'));
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate(array_merge($this->profileRules($user->id), [
            'bank_name' => 'nullable|string|max:255',
            'bank_account_name' => 'nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:255',
            'bank_routing' => 'nullable|string|max:255',
            'mobile_bank_name' => 'nullable|string|max:255',
            'mobile_bank_number' => 'nullable|string|max:255',
            'mobile_bank_type' => 'nullable|string|max:255',
        ]));

        $bankDetails = array_filter([
            'name' => $this->bank_name,
            'account_name' => $this->bank_account_name,
            'account_number' => $this->bank_account_number,
            'routing' => $this->bank_routing,
        ]);
        
        $mobileDetails = array_filter([
            'name' => $this->mobile_bank_name,
            'number' => $this->mobile_bank_number,
            'type' => $this->mobile_bank_type,
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'bank_account_details' => empty($bankDetails) ? null : $bankDetails,
            'mobile_banking_details' => empty($mobileDetails) ? null : $mobileDetails,
        ]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }

    #[Computed]
    public function stats(): array
    {
        $userId = Auth::id();
        
        $myTasksCount = \App\Models\Task::where('assignee_id', $userId)->count();
        
        $dueTasksCount = \App\Models\Task::where('assignee_id', $userId)
            ->whereNotIn('status', ['COMPLETED', 'DONE'])
            ->whereNotNull('deadline')
            ->where('deadline', '<=', now()->addDays(3))
            ->count();
            
        $totalSeconds = 0;
        foreach (\App\Models\AttendanceSession::where('user_id', $userId)->whereNotNull('check_out_time')->get() as $session) {
            $in = \Carbon\Carbon::parse($session->check_in_time);
            $out = \Carbon\Carbon::parse($session->check_out_time);
            
            $breakSeconds = 0;
            foreach (\App\Models\BreakSession::where('attendance_session_id', $session->id)->whereNotNull('end_time')->get() as $b) {
                $bIn = \Carbon\Carbon::parse($b->start_time);
                $bOut = \Carbon\Carbon::parse($b->end_time);
                $breakSeconds += max(0, $bOut->diffInSeconds($bIn));
            }
            
            $totalSeconds += max(0, $out->diffInSeconds($in) - $breakSeconds);
        }
        $workedHours = round($totalSeconds / 3600, 1);
        
        $myBadges = auth()->user()->badges()->get();
        
        return [
            'tasks' => $myTasksCount,
            'due' => $dueTasksCount,
            'hours' => $workedHours,
            'badges' => $myBadges
        ];
    }

    #[Computed]
    public function contributionsMap(): array
    {
        $userId = Auth::id();
        
        $endDate = now()->startOfDay();
        // Go back ~6 months (180 days), aligned to Sunday
        $startDate = now()->subDays(180)->startOfWeek(\Carbon\Carbon::SUNDAY);
        
        $days = $startDate->diffInDays($endDate) + 1;
        
        $contributions = \App\Models\ContributionPoint::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function($item) {
                return $item->created_at->format('Y-m-d');
            });
            
        $attendances = \App\Models\AttendanceSession::where('user_id', $userId)
            ->where('created_at', '>=', $startDate)
            ->get()
            ->groupBy(function($item) {
                return $item->created_at->format('Y-m-d');
            });
            
        $map = [];
        $currentDate = clone $startDate;
        
        for ($i = 0; $i < $days; $i++) {
            $dateStr = $currentDate->format('Y-m-d');
            $points = 0;
            
            if (isset($contributions[$dateStr])) {
                $points += $contributions[$dateStr]->sum('points');
                if ($points == 0) {
                    $points += $contributions[$dateStr]->count() * 5;
                }
            }
            if (isset($attendances[$dateStr])) {
                $points += $attendances[$dateStr]->count() * 5;
            }
            
            $level = 0;
            if ($points > 0) $level = 1;
            if ($points >= 10) $level = 2;
            if ($points >= 20) $level = 3;
            if ($points >= 40) $level = 4;
            
            $map[] = [
                'date' => $dateStr,
                'points' => $points,
                'level' => $level
            ];
            
            $currentDate->addDay();
        }
        
        return $map;
    }
}; ?>

<section class="w-full max-w-5xl mx-auto pb-10">
    <!-- Cover Banner -->
    <div class="relative mb-8 w-full">
        <!-- Modern Vibrant Cover with Heatmap -->
        <div class="h-44 w-full rounded-2xl bg-gradient-to-r from-indigo-500 via-purple-500 to-fuchsia-500 shadow-md overflow-hidden relative">
            <!-- Decorative glows -->
            <div class="absolute -top-24 -left-24 size-64 rounded-full bg-white/20 blur-3xl mix-blend-overlay"></div>
            <div class="absolute -bottom-24 right-20 size-72 rounded-full bg-white/20 blur-3xl mix-blend-overlay"></div>
            
            <!-- Github Contribution Graph Overlay -->
            <div class="absolute inset-y-0 right-4 sm:right-8 flex items-center justify-end overflow-hidden">
                <div class="flex flex-col items-end opacity-95">
                    <p class="text-[10px] text-white/80 mb-2 uppercase tracking-widest font-bold mr-1">Activity</p>
                    <div class="grid gap-1" style="grid-template-rows: repeat(7, minmax(0, 1fr)); grid-auto-flow: column;">
                        @foreach($this->contributionsMap as $day)
                            <div class="size-2 sm:size-2.5 rounded-sm backdrop-blur-sm transition-all hover:scale-125 {{ 
                                $day['level'] === 0 ? 'bg-black/10 dark:bg-black/20' : 
                                ($day['level'] === 1 ? 'bg-white/40' : 
                                ($day['level'] === 2 ? 'bg-white/60' : 
                                ($day['level'] === 3 ? 'bg-white/80' : 'bg-white shadow-[0_0_8px_rgba(255,255,255,0.8)]'))) 
                            }}" title="{{ $day['date'] }} ({{ $day['points'] }} points)"></div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        
        <div class="flex flex-col sm:flex-row items-center sm:items-end gap-6 px-6 -mt-12 relative z-10">
            <label class="cursor-pointer group relative block size-24 rounded-full p-1 bg-white dark:bg-zinc-900 shadow-md border border-zinc-200 dark:border-zinc-800">
                <div class="absolute inset-0 m-1 rounded-full bg-black/40 flex flex-col items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity z-20">
                    <flux:icon.camera class="size-6 text-white" />
                </div>
                
                <div wire:loading wire:target="avatar" class="absolute inset-0 m-1 rounded-full bg-black/50 flex flex-col items-center justify-center z-30">
                    <flux:icon.arrow-path class="size-6 text-white animate-spin" />
                </div>
                
                <div class="size-full rounded-full overflow-hidden relative z-10">
                    @if ($avatar)
                        <img src="{{ $avatar->temporaryUrl() }}" class="size-full object-cover" />
                    @elseif (auth()->user()->avatar)
                        <img src="{{ Storage::url(auth()->user()->avatar) }}" class="size-full object-cover" />
                    @else
                        <flux:avatar 
                            :name="auth()->user()->name" 
                            :initials="auth()->user()->initials()" 
                            class="size-full" 
                        />
                    @endif
                </div>
                
                <input type="file" wire:model.live="avatar" class="hidden" accept="image/*">
            </label>
            <div class="pb-2 text-center sm:text-left flex-1">
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white leading-tight">{{ auth()->user()->name }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ auth()->user()->email }}</p>
            </div>
            <div class="pb-2">
                <flux:badge variant="primary" class="font-medium px-3 py-1 text-sm rounded-full bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400 border-none shadow-sm">
                    {{ auth()->user()->role }}
                </flux:badge>
            </div>
        </div>
    </div>

    <!-- Performance & Badges -->
    <div class="mb-8 bg-white dark:bg-zinc-900 rounded-2xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-white mb-6">Performance & Awards</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <!-- Badges -->
            <div class="flex flex-col items-center sm:items-start">
                <div class="flex items-center gap-2 mb-2 text-zinc-500 dark:text-zinc-400">
                    <flux:icon.trophy class="size-4 text-amber-500" />
                    <span class="text-xs font-medium uppercase tracking-wider">My Badges</span>
                </div>
                <div class="flex gap-1 flex-wrap justify-center sm:justify-start mt-1">
                    @forelse($this->stats['badges'] as $badge)
                        <div title="{{ $badge->name }}">
                            @if(str_contains($badge->icon, '/'))
                                <img src="{{ Storage::url($badge->icon) }}" class="size-7 rounded-sm object-contain" />
                            @else
                                <flux:icon icon="{{ $badge->icon ?? 'star' }}" class="size-6 text-amber-500" />
                            @endif
                        </div>
                    @empty
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">None</span>
                    @endforelse
                </div>
            </div>

            <!-- Total Tasks -->
            <div class="flex flex-col items-center sm:items-start border-l border-zinc-100 dark:border-zinc-800 pl-0 sm:pl-6">
                <div class="flex items-center gap-2 mb-1 text-zinc-500 dark:text-zinc-400">
                    <flux:icon.clipboard-document-list class="size-4 text-blue-500" />
                    <span class="text-xs font-medium uppercase tracking-wider">Tasks</span>
                </div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['tasks'] }}</div>
            </div>

            <!-- Worked Hours -->
            <div class="flex flex-col items-center sm:items-start border-t sm:border-t-0 sm:border-l border-zinc-100 dark:border-zinc-800 pt-4 sm:pt-0 pl-0 sm:pl-6">
                <div class="flex items-center gap-2 mb-1 text-zinc-500 dark:text-zinc-400">
                    <flux:icon.clock class="size-4 text-green-500" />
                    <span class="text-xs font-medium uppercase tracking-wider">Hours</span>
                </div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['hours'] }}h</div>
            </div>

            <!-- Due Tasks -->
            <div class="flex flex-col items-center sm:items-start border-t sm:border-t-0 sm:border-l border-zinc-100 dark:border-zinc-800 pt-4 sm:pt-0 pl-0 sm:pl-6">
                <div class="flex items-center gap-2 mb-1 text-zinc-500 dark:text-zinc-400">
                    <flux:icon.exclamation-circle class="size-4 text-red-500" />
                    <span class="text-xs font-medium uppercase tracking-wider">Due Soon</span>
                </div>
                <div class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $this->stats['due'] }}</div>
            </div>
        </div>
    </div>

    <!-- Settings Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Profile Details -->
        <div class="bg-white dark:bg-zinc-900 rounded-2xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm">
            <flux:heading level="3" size="lg">{{ __('Profile Details') }}</flux:heading>
            <flux:subheading class="mb-6">{{ __('Update your name and email address.') }}</flux:subheading>
            
            <form wire:submit="updateProfileInformation" class="w-full space-y-6">
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />
                
                <div>
                    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                    @if ($this->hasUnverifiedEmail)
                        <div>
                            <flux:text class="mt-4">
                                {{ __('Your email address is unverified.') }}

                                <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </flux:link>
                            </flux:text>

                            @if (session('status') === 'verification-link-sent')
                                <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </flux:text>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="border-t border-zinc-100 dark:border-zinc-800 pt-6 mt-6">
                    <flux:heading level="4" size="md" class="mb-4">{{ __('Payment Information') }}</flux:heading>
                    
                    <div class="space-y-6">
                        <!-- Bank Account -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-200 border-b border-zinc-100 dark:border-zinc-800 pb-2">Bank Account</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input wire:model="bank_name" :label="__('Bank Name')" placeholder="e.g. City Bank" />
                                <flux:input wire:model="bank_account_name" :label="__('Account Name')" placeholder="e.g. John Doe" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <flux:input wire:model="bank_account_number" :label="__('Account Number')" placeholder="e.g. 1234567890" />
                                <flux:input wire:model="bank_routing" :label="__('Routing / Branch')" placeholder="e.g. 1234567" />
                            </div>
                        </div>

                        <!-- Mobile Banking -->
                        <div class="space-y-4">
                            <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-200 border-b border-zinc-100 dark:border-zinc-800 pb-2">Mobile Banking</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <flux:select wire:model="mobile_bank_name" :label="__('Provider')" placeholder="Select Provider">
                                    <option value="">None</option>
                                    <option value="bKash">bKash</option>
                                    <option value="Nagad">Nagad</option>
                                    <option value="Rocket">Rocket</option>
                                    <option value="Upay">Upay</option>
                                </flux:select>
                                
                                <flux:select wire:model="mobile_bank_type" :label="__('Account Type')" placeholder="Select Type">
                                    <option value="">None</option>
                                    <option value="Personal">Personal</option>
                                    <option value="Agent">Agent</option>
                                </flux:select>

                                <flux:input wire:model="mobile_bank_number" :label="__('Mobile Number')" placeholder="e.g. 01712345678" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <flux:button variant="primary" type="submit" data-test="update-profile-button">
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>

        <div class="flex flex-col gap-8">
            <!-- Appearance Settings -->
            <div class="bg-white dark:bg-zinc-900 rounded-2xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                <flux:heading level="3" size="lg">{{ __('Appearance') }}</flux:heading>
                <flux:subheading class="mb-6">{{ __('Update the appearance settings for your account') }}</flux:subheading>
                
                <flux:radio.group x-data variant="segmented" x-model="$flux.appearance">
                    <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
                    <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
                    <flux:radio value="system" icon="computer-desktop">{{ __('System') }}</flux:radio>
                </flux:radio.group>
            </div>

            <!-- Account Actions -->
            @if ($this->showDeleteUser)
            <div class="bg-white dark:bg-zinc-900 rounded-2xl p-6 border border-zinc-200 dark:border-zinc-800 shadow-sm">
                 <livewire:pages::settings.delete-user-form />
            </div>
            @endif
        </div>
    </div>
</section>
