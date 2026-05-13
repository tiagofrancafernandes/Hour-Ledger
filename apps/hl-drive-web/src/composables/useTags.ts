import { ref } from 'vue';
import api from '@/services/api';
import type { Tag } from '@/types';

export function useTags() {
    const tags = ref<Tag[]>([]);
    const loading = ref(false);
    const error = ref<string | null>(null);

    async function fetchTags(search = '') {
        loading.value = true;
        error.value = null;

        try {
            const params = search ? `?search=${encodeURIComponent(search)}` : '';

            tags.value = await api.get<Tag[]>(`/tags${params}`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch tags';
        } finally {
            loading.value = false;
        }
    }

    async function createTag(name: string): Promise<Tag> {
        loading.value = true;
        error.value = null;

        try {
            const newTag = await api.post<Tag>('/tags', { name });

            tags.value.push(newTag);

            return newTag;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create tag';

            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function deleteTag(id: number) {
        loading.value = true;
        error.value = null;

        try {
            await api.delete(`/tags/${id}`);

            tags.value = tags.value.filter((t) => t.id !== id);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to delete tag';

            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        tags,
        loading,
        error,
        fetchTags,
        createTag,
        deleteTag,
    };
}
