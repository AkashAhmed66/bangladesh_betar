@extends('layouts.admin')

@section('title', $workflow ? 'Edit Workflow' : 'New Workflow')

@php
    $initialStages = old('stages', ($workflow?->stages ?? collect())
        ->map(fn ($s) => ['name' => $s->name, 'approver_role' => $s->approver_role])
        ->values()->all());
    if (empty($initialStages)) {
        $initialStages = [['name' => '', 'approver_role' => '']];
    }
@endphp

@section('content')
<x-page-header :title="$workflow ? 'Edit: '.$workflow->name : 'New Approval Workflow'"
               subtitle="Stages run in order; each is actioned by members of the assigned role (FR-WRK-01/05)" />

<form method="POST" action="{{ $workflow ? route('admin.workflows.update', $workflow) : route('admin.workflows.store') }}" class="max-w-3xl"
      x-data="{ stages: {{ Illuminate\Support\Js::from($initialStages) }} }">
    @csrf
    @if ($workflow) @method('PUT') @endif

    <div class="card mb-5">
        <div class="card-body grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-form.input label="Workflow name" name="name" :value="$workflow?->name" required />
            <x-form.select label="Content type" name="content_type" :value="$workflow?->content_type ?? 'default'" required
                           :options="collect($contentTypes)->mapWithKeys(fn ($t) => [$t => ucfirst($t)])->all()"
                           help="One active workflow serves each content type; 'default' is the fallback." />
            <div class="sm:col-span-2"><x-form.textarea label="Description" name="description" :value="$workflow?->description" rows="2" /></div>
            <x-form.input label="Escalation hours" name="escalation_hours" type="number" :value="$workflow?->escalation_hours ?? 72" required
                          help="Flag pending items as overdue after this many hours (FR-WRK-08)." />
            <div class="flex items-end">
                <x-form.toggle label="Active" name="is_active" :checked="(bool) ($workflow?->is_active ?? true)"
                               help="Only active workflows are used to route new submissions." />
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="font-semibold text-slate-800 dark:text-slate-100">Approval Stages</h3>
            <span class="text-xs text-slate-400" x-text="stages.length + ' stage' + (stages.length === 1 ? '' : 's')"></span>
        </div>
        <div class="card-body space-y-3">
            @error('stages')<p class="form-error">{{ $message }}</p>@enderror

            <template x-for="(stage, index) in stages" :key="index">
                <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-800">
                    <span class="mt-2.5 w-6 shrink-0 text-center text-xs font-semibold text-slate-400" x-text="index + 1"></span>
                    <div class="flex-1">
                        <input type="text" :name="`stages[${index}][name]`" x-model="stage.name"
                               placeholder="Stage name (e.g. Technical QC)" class="form-input" required>
                    </div>
                    <div class="w-56 shrink-0">
                        <select :name="`stages[${index}][approver_role]`" x-model="stage.approver_role" class="form-input" required>
                            <option value="">Select approver role…</option>
                            @foreach ($roles as $role)
                                <option value="{{ $role }}">{{ $role }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" class="mt-1.5 shrink-0 text-slate-400 hover:text-rose-500"
                            @click="stages.splice(index, 1)" x-show="stages.length > 1">
                        <x-icon name="trash" class="size-4" />
                    </button>
                </div>
            </template>

            <button type="button" class="btn-secondary btn-sm" @click="stages.push({ name: '', approver_role: '' })">
                <x-icon name="plus" class="size-4" /> Add Stage
            </button>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-200 px-5 py-4 dark:border-slate-800">
            <a href="{{ route('admin.workflows.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">{{ $workflow ? 'Save Changes' : 'Create Workflow' }}</button>
        </div>
    </div>
</form>
@endsection
