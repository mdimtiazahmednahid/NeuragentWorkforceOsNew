<?php

use Livewire\Volt\Component;
use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Support\Facades\Auth;

new class extends Component {
    public $project;
    public $documents = [];
    
    // Form fields
    public $title = '';
    public $type = 'NOTE';
    public $url = '';
    public $content = '';
    
    public $isAdding = false;
    public $isEditing = false;
    public $editDocId = null;

    protected $listeners = ['openProjectDocs' => 'loadProject'];

    public function loadProject($projectId)
    {
        $this->project = Project::findOrFail($projectId);
        $this->loadDocuments();
        $this->isAdding = false;
        $this->isEditing = false;
        $this->resetForm();
    }

    public function loadDocuments()
    {
        if ($this->project) {
            $this->documents = ProjectDocument::with('creator')
                ->where('project_id', $this->project->id)
                ->orderBy('created_at', 'desc')
                ->get();
        }
    }

    public function toggleAdd()
    {
        $this->isAdding = !$this->isAdding;
        $this->isEditing = false;
        if (!$this->isAdding) {
            $this->resetForm();
        }
    }
    
    public function cancelEdit()
    {
        $this->isEditing = false;
        $this->isAdding = false;
        $this->resetForm();
    }
    
    public function editDocument($id)
    {
        $doc = ProjectDocument::findOrFail($id);
        $this->editDocId = $doc->id;
        $this->title = $doc->title;
        $this->type = $doc->type;
        $this->url = $doc->url;
        $this->content = $doc->content;
        
        $this->isEditing = true;
        $this->isAdding = false;
    }

    public function resetForm()
    {
        $this->title = '';
        $this->type = 'NOTE';
        $this->url = '';
        $this->content = '';
    }

    public function saveDocument()
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:NOTE,LINK,FILE',
            'url' => 'required_if:type,LINK|nullable|url',
        ]);

        if ($this->isEditing) {
            $doc = ProjectDocument::where('id', $this->editDocId)->where('project_id', $this->project->id)->firstOrFail();
            $doc->update([
                'title' => $this->title,
                'type' => $this->type,
                'url' => $this->url,
                'content' => $this->content,
            ]);
            \Flux\Flux::toast('Document updated successfully.', variant: 'success');
        } else {
            ProjectDocument::create([
                'project_id' => $this->project->id,
                'title' => $this->title,
                'type' => $this->type,
                'url' => $this->url,
                'content' => $this->content,
                'created_by' => Auth::id(),
            ]);
            \Flux\Flux::toast('Document added successfully.', variant: 'success');
        }

        $this->loadDocuments();
        $this->isEditing = false;
        $this->isAdding = false;
        $this->resetForm();
    }

    public function deleteDocument($id)
    {
        ProjectDocument::where('id', $id)->where('project_id', $this->project->id)->delete();
        $this->loadDocuments();
    }
}; ?>

<div>
    <flux:modal name="project-docs" class="md:w-[700px]">
        @if($project)
            <div class="flex justify-between items-start mb-6 border-b border-zinc-100 dark:border-zinc-800 pb-4">
                <div>
                    <h2 class="text-xl font-bold text-zinc-900 dark:text-white">{{ $project->name }} Docs</h2>
                    <p class="text-sm text-zinc-500">Manage documentation, links, and resources.</p>
                </div>
                <div class="pr-8">
                    @if(!$isAdding && !$isEditing)
                        <flux:button wire:click="toggleAdd" variant="primary" size="sm" icon="plus">Add New</flux:button>
                    @endif
                </div>
            </div>

            @if($isAdding || $isEditing)
                <div class="bg-zinc-50 dark:bg-zinc-800/50 p-4 rounded-xl mb-6 border border-zinc-200 dark:border-zinc-700">
                    <div class="mb-4">
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $isEditing ? 'Edit Document' : 'Add New Document' }}</h3>
                    </div>
                    <form wire:submit="saveDocument" class="space-y-4">
                        <flux:input wire:model="title" label="Title" placeholder="e.g. API Documentation" />
                        
                        <flux:radio.group wire:model.live="type" label="Type">
                            <flux:radio value="NOTE" label="Note / Wiki" />
                            <flux:radio value="LINK" label="External Link" />
                        </flux:radio.group>
                        
                        @if($type === 'LINK')
                            <flux:input wire:model="url" label="URL" placeholder="https://..." type="url" />
                        @elseif($type === 'NOTE')
                            <flux:textarea wire:model="content" label="Content" rows="6" placeholder="Write documentation here..." />
                        @endif
                        
                        <div class="flex justify-end gap-2 pt-2">
                            <flux:button wire:click="cancelEdit" variant="ghost" size="sm">Cancel</flux:button>
                            <flux:button type="submit" variant="primary" size="sm">Save Document</flux:button>
                        </div>
                    </form>
                </div>
            @endif

            <div class="space-y-4 max-h-[60vh] overflow-y-auto">
                @forelse($documents as $doc)
                    <div class="bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-4 rounded-xl">
                        <div class="flex justify-between items-start mb-2">
                            <div class="flex items-center gap-2">
                                @if($doc->type === 'LINK')
                                    <flux:icon.link class="size-5 text-indigo-500" />
                                @else
                                    <flux:icon.document-text class="size-5 text-amber-500" />
                                @endif
                                <h3 class="font-bold text-zinc-900 dark:text-white">{{ $doc->title }}</h3>
                            </div>
                            <div class="flex items-center gap-1">
                                <flux:button wire:click="editDocument({{ $doc->id }})" variant="ghost" size="sm" icon="pencil-square" class="text-indigo-500 hover:bg-indigo-50 dark:hover:bg-indigo-900/20" />
                                <flux:button wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Are you sure you want to delete this document?" variant="ghost" size="sm" icon="trash" class="text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20" />
                            </div>
                        </div>
                        
                        @if($doc->type === 'LINK')
                            <a href="{{ $doc->url }}" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline break-all mb-2 block">
                                {{ $doc->url }}
                            </a>
                        @elseif($doc->type === 'NOTE')
                            <div class="text-sm text-zinc-600 dark:text-zinc-300 prose prose-sm dark:prose-invert mb-2">
                                {!! nl2br(e($doc->content)) !!}
                            </div>
                        @endif
                        
                        <div class="text-xs text-zinc-500 mt-2 flex items-center gap-1">
                            <span>Added by {{ $doc->creator?->name }}</span> &bull; <span>{{ $doc->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    @if(!$isAdding)
                        <div class="text-center py-8">
                            <flux:icon.document class="size-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-3" />
                            <p class="text-zinc-500">No documentation found for this project.</p>
                            <flux:button wire:click="toggleAdd" variant="ghost" size="sm" class="mt-2">Add the first document</flux:button>
                        </div>
                    @endif
                @endforelse
            </div>
        @else
            <div class="p-8 text-center text-zinc-500">Loading project docs...</div>
        @endif
    </flux:modal>
</div>
