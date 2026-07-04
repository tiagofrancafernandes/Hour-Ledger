import { ref } from 'vue';
import api from '@/services/api';
import type { Lesson, LessonForm, LessonConsumptionForm } from '@/types';

export function useLessons() {
    const loading = ref(false);
    const error = ref<string | null>(null);
    const lessons = ref<Lesson[]>([]);

    async function fetchLessons(): Promise<Lesson[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<Lesson[]>('/lessons');
            lessons.value = Array.isArray(response) ? response : [];
            return lessons.value;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch lessons';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchLesson(id: number): Promise<Lesson> {
        loading.value = true;
        error.value = null;

        try {
            return await api.get<Lesson>(`/lessons/${id}`);
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch lesson';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function createLesson(data: LessonForm): Promise<Lesson> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Lesson>('/lessons', data);
            const newLesson = response;
            lessons.value.push(newLesson);
            return newLesson;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create lesson';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function updateLesson(id: number, data: Partial<LessonForm>): Promise<Lesson> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.put<Lesson>(`/lessons/${id}`, data);
            const index = lessons.value.findIndex((l) => l.id === id);
            if (index !== -1) {
                lessons.value[index] = response;
            }
            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to update lesson';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function completeLesson(id: number, consumptionData: LessonConsumptionForm): Promise<Lesson> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Lesson>(`/lessons/${id}/complete`, consumptionData);
            const index = lessons.value.findIndex((l) => l.id === id);
            if (index !== -1) {
                lessons.value[index] = response;
            }
            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to complete lesson';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function cancelLesson(id: number): Promise<Lesson> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.post<Lesson>(`/lessons/${id}/cancel`, {});
            const index = lessons.value.findIndex((l) => l.id === id);
            if (index !== -1) {
                lessons.value[index] = response;
            }
            return response;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to cancel lesson';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchStudentLessons(studentId: number): Promise<Lesson[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<Lesson[]>(`/students/${studentId}/lessons`);
            return Array.isArray(response) ? response : [];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch student lessons';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function fetchInstructorLessons(instructorId: number): Promise<Lesson[]> {
        loading.value = true;
        error.value = null;

        try {
            const response = await api.get<Lesson[]>(`/instructors/${instructorId}/lessons`);
            return Array.isArray(response) ? response : [];
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to fetch instructor lessons';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return {
        loading,
        error,
        lessons,
        fetchLessons,
        fetchLesson,
        createLesson,
        updateLesson,
        completeLesson,
        cancelLesson,
        fetchStudentLessons,
        fetchInstructorLessons,
    };
}
