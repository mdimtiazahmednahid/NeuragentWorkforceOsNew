<?php

use Livewire\Volt\Component;
use App\Models\Message;
use App\Models\User;
use App\Models\Project;
use App\Models\ProjectMember;
use Livewire\Attributes\Computed;

new class extends Component {
    public $isOpen = false;
    public $isMinimized = false;
    public $activeTab = 'direct'; // 'direct' or 'team'
    
    // Direct Chat
    public $users;
    public $selectedUserId = null;
    public $directMessages = [];
    public $directMessageContent = '';
    
    // Team Chat
    public $projects;
    public $selectedProjectId = null;
    public $teamMessages = [];
    public $teamMessageContent = '';

    protected $listeners = [
        'openGlobalChat' => 'open',
        'openGlobalChatToProject' => 'openToProject'
    ];

    public function mount()
    {
        $this->loadUsers();
        $this->loadProjects();
    }
    
    public function toggleMinimize()
    {
        $this->isMinimized = !$this->isMinimized;
    }
    
    public function openToProject($projectId)
    {
        $this->isOpen = true;
        $this->isMinimized = false;
        $this->activeTab = 'team';
        $this->selectedProjectId = $projectId;
        $this->loadTeamMessages();
    }
    
    public function open()
    {
        $this->isOpen = true;
        $this->isMinimized = false;
        if ($this->activeTab === 'direct' && $this->selectedUserId) {
            $this->loadDirectMessages();
        } elseif ($this->activeTab === 'team' && $this->selectedProjectId) {
            $this->loadTeamMessages();
        }
    }
    
    public function close()
    {
        $this->isOpen = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    #[Computed]
    public function unreadCounts()
    {
        return Message::where('receiver_id', auth()->id())
            ->whereNull('project_id')
            ->where('is_read', false)
            ->selectRaw('sender_id, count(*) as count')
            ->groupBy('sender_id')
            ->pluck('count', 'sender_id')
            ->toArray();
    }
    
    // --- Direct Chat ---
    public function loadUsers()
    {
        $this->users = User::where('id', '!=', auth()->id())->get()->map(function($user) {
            $lastMsg = Message::whereNull('project_id')
                ->where(function($q) use ($user) {
                    $q->where('sender_id', auth()->id())->where('receiver_id', $user->id)
                      ->orWhere('sender_id', $user->id)->where('receiver_id', auth()->id());
                })->orderBy('created_at', 'desc')->first();
            
            $user->last_message = $lastMsg;
            $user->last_message_at = $lastMsg ? $lastMsg->created_at : null;
            return $user;
        })->sortByDesc('last_message_at')->values();
    }

    public function selectUser($userId)
    {
        $this->selectedUserId = $userId;
        $this->loadDirectMessages();
    }
    
    public function loadDirectMessages()
    {
        if (!$this->selectedUserId || !$this->isOpen) return;
        
        $this->directMessages = Message::with(['sender', 'receiver'])
            ->whereNull('project_id')
            ->where(function($query) {
                $query->where(function($q) {
                    $q->where('sender_id', auth()->id())->where('receiver_id', $this->selectedUserId);
                })->orWhere(function($q) {
                    $q->where('sender_id', $this->selectedUserId)->where('receiver_id', auth()->id());
                });
            })
            ->orderBy('created_at', 'asc')
            ->get();
            
        Message::where('sender_id', $this->selectedUserId)
            ->where('receiver_id', auth()->id())
            ->whereNull('project_id')
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
    
    public function sendDirectMessage()
    {
        if (!$this->selectedUserId || empty(trim($this->directMessageContent))) return;
        
        Message::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $this->selectedUserId,
            'message' => trim($this->directMessageContent),
            'is_read' => false
        ]);
        
        $this->directMessageContent = '';
        $this->loadDirectMessages();
    }

    // --- Team Chat ---
    public function loadProjects()
    {
        $this->projects = Project::with(['members' => function($q) {
            $q->where('user_id', auth()->id());
        }])->get()->map(function($project) {
            $lastMsg = Message::where('project_id', $project->id)
                ->orderBy('created_at', 'desc')->first();
            
            $project->last_message = $lastMsg;
            $project->last_message_at = $lastMsg ? $lastMsg->created_at : null;
            return $project;
        })->sortByDesc('last_message_at')->values();
    }

    public function selectProject($projectId)
    {
        $this->selectedProjectId = $projectId;
        $this->loadTeamMessages();
    }
    
    public function requestToJoin($projectId)
    {
        ProjectMember::updateOrCreate(
            ['project_id' => $projectId, 'user_id' => auth()->id()],
            ['status' => 'PENDING', 'role' => 'MEMBER']
        );
        $this->loadProjects();
    }
    
    public function approveRequest($projectId, $userId)
    {
        ProjectMember::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->update(['status' => 'JOINED']);
        $this->loadProjects();
    }
    
    public function addMemberExplicitly($projectId, $userId)
    {
        if (!$userId) return;
        
        ProjectMember::updateOrCreate(
            ['project_id' => $projectId, 'user_id' => $userId],
            ['status' => 'JOINED', 'role' => 'MEMBER']
        );
        $this->loadProjects();
    }

    public function loadTeamMessages()
    {
        if (!$this->selectedProjectId || !$this->isOpen) return;
        
        $this->teamMessages = Message::with(['sender'])
            ->where('project_id', $this->selectedProjectId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
    
    public function sendTeamMessage()
    {
        if (!$this->selectedProjectId || empty(trim($this->teamMessageContent))) return;
        
        Message::create([
            'sender_id' => auth()->id(),
            'project_id' => $this->selectedProjectId,
            'message' => trim($this->teamMessageContent),
            'is_read' => false
        ]);
        
        $this->teamMessageContent = '';
        $this->loadTeamMessages();
    }
    
    public function pollMessages()
    {
        if (!$this->isOpen || $this->isMinimized) return;
        
        $this->loadUsers();
        $this->loadProjects();
        
        if ($this->activeTab === 'direct') $this->loadDirectMessages();
        if ($this->activeTab === 'team') $this->loadTeamMessages();
    }
}; ?>

<div>
    <!-- Global Messenger UI -->
    @if($isOpen)
        @if($isMinimized)
            <!-- Floating Bubble -->
            <button wire:click="toggleMinimize" class="fixed bottom-6 right-6 z-50 p-4 bg-indigo-600 hover:bg-indigo-700 text-white rounded-full shadow-lg transition-transform hover:scale-105 flex items-center justify-center">
                <flux:icon.chat-bubble-left-right class="size-6" />
                @if(array_sum($this->unreadCounts) > 0)
                    <span class="absolute top-0 right-0 size-3 bg-red-500 border-2 border-white rounded-full"></span>
                @endif
            </button>
        @else
            <div class="fixed bottom-0 right-0 z-50 sm:mr-6 sm:mb-6 flex flex-col w-full sm:w-[400px] h-full sm:h-[600px] max-h-screen bg-white dark:bg-zinc-900 sm:rounded-t-xl sm:rounded-xl shadow-2xl border border-zinc-200 dark:border-zinc-800 overflow-hidden" 
                 x-data="{ tab: @entangle('activeTab') }"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-8 scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                 x-transition:leave-end="opacity-0 translate-y-8 scale-95"
                 wire:poll.5s="pollMessages">
                
                <!-- Header -->
                <div class="bg-indigo-600 dark:bg-indigo-700 text-white p-4 flex justify-between items-center sm:rounded-t-xl">
                    <div class="flex items-center gap-2 font-medium">
                        <flux:icon.chat-bubble-left-right class="size-5" />
                        Messenger
                    </div>
                    <div class="flex items-center gap-3">
                        <button wire:click="toggleMinimize" class="text-indigo-100 hover:text-white transition-colors" title="Minimize">
                            <flux:icon.minus class="size-5" />
                        </button>
                        <button wire:click="close" class="text-indigo-100 hover:text-white transition-colors" title="Close">
                            <flux:icon.x-mark class="size-5" />
                        </button>
                    </div>
                </div>
            
            <!-- Tabs -->
            <div class="flex border-b border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                <button wire:click="switchTab('direct')" class="flex-1 py-3 text-sm font-medium border-b-2 transition-colors" :class="tab === 'direct' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'">
                    Direct
                </button>
                <button wire:click="switchTab('team')" class="flex-1 py-3 text-sm font-medium border-b-2 transition-colors" :class="tab === 'team' ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300'">
                    Team
                </button>
            </div>
            
            <!-- Body -->
            <div class="flex-1 flex overflow-hidden">
                
                <!-- DIRECT CHAT TAB -->
                <div x-show="tab === 'direct'" class="flex-1 flex w-full h-full">
                    
                    @if(!$selectedUserId)
                        <div class="w-full h-full overflow-y-auto p-2 space-y-1 bg-white dark:bg-zinc-900">
                            @foreach($users as $user)
                                @php $unread = $this->unreadCounts[$user->id] ?? 0; @endphp
                                <button wire:click="selectUser({{ $user->id }})" class="w-full flex items-center gap-3 p-3 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-800/50 transition-colors text-left relative">
                                    <div class="relative">
                                        <flux:avatar :name="$user->name" :initials="$user->initials()" size="sm" />
                                        @if($unread > 0)
                                            <div class="absolute -top-1 -right-1 size-3 bg-blue-500 rounded-full border-2 border-white dark:border-zinc-900"></div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium {{ $unread > 0 ? 'text-zinc-900 dark:text-white font-bold' : 'text-zinc-700 dark:text-zinc-300' }} truncate">{{ $user->name }}</div>
                                        @if($user->last_message)
                                            <div class="text-xs truncate mt-0.5 {{ $unread > 0 ? 'text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 dark:text-zinc-400' }}">
                                                {{ $user->last_message->sender_id === auth()->id() ? 'You: ' : '' }}{{ $user->last_message->message }} • {{ \Carbon\Carbon::parse($user->last_message->created_at)->diffForHumans(null, true, true) }}
                                            </div>
                                        @endif
                                    </div>
                                    @if($unread > 0)
                                        <div class="text-xs font-bold text-blue-500 bg-blue-50 dark:bg-blue-500/10 px-2 py-0.5 rounded-full">{{ $unread }}</div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @else
                        <!-- Active Direct Chat -->
                        <div class="w-full flex flex-col h-full bg-zinc-50 dark:bg-zinc-900/30">
                            <div class="p-3 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-3">
                                <button wire:click="$set('selectedUserId', null)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                    <flux:icon.arrow-left class="size-5" />
                                </button>
                                <flux:avatar :name="$users->firstWhere('id', $selectedUserId)->name ?? 'User'" size="sm" />
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $users->firstWhere('id', $selectedUserId)->name ?? 'User' }}</div>
                            </div>
                            
                            <div class="flex-1 p-4 overflow-y-auto space-y-4" id="direct-messages">
                                @forelse($directMessages as $message)
                                    <div class="flex flex-col {{ $message->sender_id === auth()->id() ? 'items-end' : 'items-start' }}">
                                        <div class="max-w-[85%] rounded-2xl px-4 py-2 text-sm {{ $message->sender_id === auth()->id() ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white rounded-bl-none shadow-sm border border-zinc-100 dark:border-zinc-700' }}">
                                            {{ $message->message }}
                                        </div>
                                        <div class="text-[10px] text-zinc-400 mt-1 mx-1">
                                            {{ \Carbon\Carbon::parse($message->created_at)->format('H:i') }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="h-full flex items-center justify-center text-zinc-500 text-sm">
                                        Say hi!
                                    </div>
                                @endforelse
                            </div>
                            
                            <div class="p-3 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
                                <form wire:submit="sendDirectMessage" class="flex gap-2">
                                    <flux:input wire:model="directMessageContent" placeholder="Type a message..." class="flex-1 text-sm" required />
                                    <flux:button type="submit" variant="primary" icon="paper-airplane" class="px-3" />
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- TEAM CHAT TAB -->
                <div x-show="tab === 'team'" class="flex-1 flex w-full h-full" style="display: none;">
                    @if(!$selectedProjectId)
                        <!-- Projects List -->
                        <div class="w-full h-full overflow-y-auto p-2 space-y-2 bg-white dark:bg-zinc-900">
                            @foreach($projects as $project)
                                @php
                                    $membership = $project->members->first();
                                    $status = $membership ? $membership->status : 'NONE'; // JOINED, PENDING, NONE
                                    
                                    // Admins can bypass restrictions
                                    if (auth()->user()->isAdmin) {
                                        $status = 'OWNER'; 
                                    } elseif ($project->owner_id === auth()->id() && $status !== 'JOINED') {
                                        $status = 'OWNER'; 
                                    }
                                @endphp
                                
                                <div class="p-3 rounded-lg border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/50">
                                    <div class="flex justify-between items-start mb-1">
                                        <div class="font-medium text-zinc-900 dark:text-white">{{ $project->name }}</div>
                                        @if($status === 'JOINED' || $status === 'OWNER')
                                            <flux:badge size="sm" color="green">Joined</flux:badge>
                                        @elseif($status === 'PENDING')
                                            <flux:badge size="sm" color="amber">Pending</flux:badge>
                                        @endif
                                    </div>
                                    @if($project->last_message)
                                        <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate mb-3">
                                            {{ $project->last_message->sender_id === auth()->id() ? 'You: ' : ($project->last_message->sender->name . ': ') }}{{ $project->last_message->message }} • {{ \Carbon\Carbon::parse($project->last_message->created_at)->diffForHumans(null, true, true) }}
                                        </div>
                                    @endif
                                    
                                    @if($status === 'JOINED' || $status === 'OWNER')
                                        <flux:button wire:click="selectProject({{ $project->id }})" variant="primary" size="sm" class="w-full">Open Chat</flux:button>
                                        
                                        <!-- If owner, show pending requests and explicit add -->
                                        @if($status === 'OWNER')
                                            @php
                                                $pendingRequests = \App\Models\ProjectMember::where('project_id', $project->id)->where('status', 'PENDING')->get();
                                            @endphp
                                            @if($pendingRequests->count() > 0)
                                                <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700">
                                                    <div class="text-xs font-semibold text-zinc-500 mb-2">Pending Requests:</div>
                                                    @foreach($pendingRequests as $req)
                                                        <div class="flex items-center justify-between text-sm bg-white dark:bg-zinc-800 p-2 rounded mb-1">
                                                            <span class="truncate pr-2">{{ $req->user->name }}</span>
                                                            <flux:button wire:click="approveRequest({{ $project->id }}, {{ $req->user_id }})" size="xs" variant="primary">Approve</flux:button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            
                                            <!-- Explicitly add user -->
                                            <div x-data="{ addUserId: '' }" class="mt-2 pt-2 border-t border-zinc-200 dark:border-zinc-700 flex gap-2 items-center">
                                                <select x-model="addUserId" class="flex-1 text-xs bg-transparent border-b border-zinc-300 dark:border-zinc-600 focus:border-indigo-500 py-1 px-0 outline-none dark:text-white">
                                                    <option value="">+ Add user directly...</option>
                                                    @foreach($users as $u)
                                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                                    @endforeach
                                                </select>
                                                <flux:button x-on:click="if(addUserId) { $wire.addMemberExplicitly({{ $project->id }}, addUserId); addUserId = ''; }" size="xs" variant="ghost">Add</flux:button>
                                            </div>
                                        @endif
                                    @elseif($status === 'PENDING')
                                        <flux:button variant="subtle" size="sm" class="w-full" disabled>Request Sent</flux:button>
                                    @else
                                        <flux:button wire:click="requestToJoin({{ $project->id }})" variant="subtle" size="sm" class="w-full">Request to Join</flux:button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Active Team Chat -->
                        <div class="w-full flex flex-col h-full bg-zinc-50 dark:bg-zinc-900/30">
                            <div class="p-3 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-800 flex items-center gap-3">
                                <button wire:click="$set('selectedProjectId', null)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                    <flux:icon.arrow-left class="size-5" />
                                </button>
                                <div class="w-8 h-8 rounded-md bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 font-bold text-sm">
                                    #
                                </div>
                                <div class="font-medium text-zinc-900 dark:text-white truncate">
                                    {{ $projects->firstWhere('id', $selectedProjectId)->name ?? 'Team' }}
                                </div>
                            </div>
                            
                            <div class="flex-1 p-4 overflow-y-auto space-y-4" id="team-messages">
                                @forelse($teamMessages as $message)
                                    <div class="flex flex-col {{ $message->sender_id === auth()->id() ? 'items-end' : 'items-start' }}">
                                        @if($message->sender_id !== auth()->id())
                                            <div class="text-[10px] text-zinc-500 mb-0.5 ml-1">{{ $message->sender->name }}</div>
                                        @endif
                                        <div class="max-w-[85%] rounded-2xl px-4 py-2 text-sm {{ $message->sender_id === auth()->id() ? 'bg-indigo-600 text-white rounded-br-none' : 'bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white rounded-bl-none shadow-sm border border-zinc-100 dark:border-zinc-700' }}">
                                            {{ $message->message }}
                                        </div>
                                        <div class="text-[10px] text-zinc-400 mt-1 mx-1">
                                            {{ \Carbon\Carbon::parse($message->created_at)->format('H:i') }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="h-full flex items-center justify-center text-zinc-500 text-sm">
                                        No messages in this team yet.
                                    </div>
                                @endforelse
                            </div>
                            
                            <div class="p-3 bg-white dark:bg-zinc-900 border-t border-zinc-200 dark:border-zinc-800">
                                <form wire:submit="sendTeamMessage" class="flex gap-2">
                                    <flux:input wire:model="teamMessageContent" placeholder="Message team..." class="flex-1 text-sm" required />
                                    <flux:button type="submit" variant="primary" icon="paper-airplane" class="px-3" />
                                </form>
                            </div>
                        </div>
                    @endif
                </div>

            </div>
        </div>
        @endif
    @endif
</div>
