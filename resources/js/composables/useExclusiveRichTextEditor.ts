import { computed, shallowRef } from 'vue';

const activeEditorId = shallowRef<symbol | null>(null);

export function useExclusiveRichTextEditor() {
    const editorId = Symbol('rich-text-editor');
    const isEditorActive = computed(() => activeEditorId.value === editorId);

    const activateEditor = () => {
        activeEditorId.value = editorId;
    };

    const deactivateEditor = () => {
        if (activeEditorId.value === editorId) {
            activeEditorId.value = null;
        }
    };

    return {
        isEditorActive,
        activateEditor,
        deactivateEditor,
    };
}
