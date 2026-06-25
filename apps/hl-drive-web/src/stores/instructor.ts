import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { api } from '@/services/api';
import type { GenericResponse } from '@/types';
import type {
    InstructorUser,
    StudentUser,
    Invitation,
    InstructorStudentLink,
    CreateInvitationPayload,
} from '@/types/instructor';

const STORAGE_KEYS = {
    ACTIVE_INSTRUCTOR_ID: 'instructor_active_id',
};

interface InstructorState {
    activeInstructorId: number | null;
    myInstructors: InstructorStudentLink[];
    myStudents: StudentUser[];
    pendingInvitations: Invitation[];
    loading: boolean;
    error: string | null;
}

export const useInstructorStore = defineStore('instructor', () => {
    // State
    const state = ref<InstructorState>({
        activeInstructorId: null,
        myInstructors: [],
        myStudents: [],
        pendingInvitations: [],
        loading: false,
        error: null,
    });

    // Getters
    const activeInstructorId = computed(() => state.value.activeInstructorId);
    const myInstructors = computed(() => state.value.myInstructors);
    const myStudents = computed(() => state.value.myStudents);
    const pendingInvitations = computed(() => state.value.pendingInvitations);
    const loading = computed(() => state.value.loading);
    const error = computed(() => state.value.error);

    // Helper Methods
    function loadFromStorage(): void {
        const storedInstructorId = localStorage.getItem(STORAGE_KEYS.ACTIVE_INSTRUCTOR_ID);

        if (storedInstructorId) {
            const instructorId = parseInt(storedInstructorId, 10);
            state.value.activeInstructorId = instructorId;
        }
    }

    function saveToStorage(): void {
        if (state.value.activeInstructorId) {
            localStorage.setItem(STORAGE_KEYS.ACTIVE_INSTRUCTOR_ID, String(state.value.activeInstructorId));
        } else {
            localStorage.removeItem(STORAGE_KEYS.ACTIVE_INSTRUCTOR_ID);
        }
    }

    function clearStorage(): void {
        localStorage.removeItem(STORAGE_KEYS.ACTIVE_INSTRUCTOR_ID);
    }

    function getActiveInstructor(): InstructorUser | null {
        if (!state.value.activeInstructorId) {
            return null;
        }

        const link = state.value.myInstructors.find((link) => link.instructor?.id === state.value.activeInstructorId);

        if (!link || !link.instructor) {
            return null;
        }

        return link.instructor;
    }

    function getInstructorName(): string {
        const activeInstructor = getActiveInstructor();

        if (!activeInstructor) {
            return '';
        }

        return activeInstructor.name;
    }

    function getInstructorEmail(): string {
        const activeInstructor = getActiveInstructor();

        if (!activeInstructor) {
            return '';
        }

        return activeInstructor.email;
    }

    function getPendingInvitationCount(): number {
        return state.value.pendingInvitations.length;
    }

    // Actions
    function setActiveInstructor(instructorId: number): void {
        const link = state.value.myInstructors.find((link) => link.instructor?.id === instructorId);

        if (!link) {
            state.value.error = 'Instructor not found';

            return;
        }

        state.value.activeInstructorId = instructorId;
        state.value.error = null;
        saveToStorage();

        // Emit custom event to notify headers need update
        window.dispatchEvent(new CustomEvent('instructor-changed', { detail: { instructorId } }));
    }

    async function fetchMyInstructors(): Promise<void> {
        state.value.loading = true;
        state.value.error = null;

        try {
            const response = await api.get<GenericResponse<InstructorStudentLink[]>>('/instructor-links/my-instructors');

            let linksList: InstructorStudentLink[] = [];

            if (Array.isArray(response.data)) {
                linksList = response.data;
            } else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                const dataProperty = response.data.data as InstructorStudentLink[];
                linksList = dataProperty;
            }

            state.value.myInstructors = linksList;

            // Validate active instructor after fetch
            if (state.value.activeInstructorId) {
                const isValid = state.value.myInstructors.some((link) => link.instructor?.id === state.value.activeInstructorId);

                if (!isValid && linksList.length > 0) {
                    // Auto-select first instructor if current is invalid
                    const firstInstructor = linksList[0];

                    if (firstInstructor.instructor) {
                        state.value.activeInstructorId = firstInstructor.instructor.id;
                        saveToStorage();
                    }
                }
            } else if (linksList.length > 0) {
                // Auto-select first instructor if none is active
                const firstInstructor = linksList[0];

                if (firstInstructor.instructor) {
                    state.value.activeInstructorId = firstInstructor.instructor.id;
                    saveToStorage();
                }
            }
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to fetch instructors';
            state.value.myInstructors = [];
            state.value.activeInstructorId = null;
            clearStorage();
        } finally {
            state.value.loading = false;
        }
    }

    async function fetchMyStudents(): Promise<void> {
        if (!state.value.activeInstructorId) {
            state.value.myStudents = [];

            return;
        }

        state.value.loading = true;
        state.value.error = null;

        try {
            const response = await api.get<GenericResponse<StudentUser[]>>('/instructor-links/my-students', {
                instructor_id: state.value.activeInstructorId,
            });

            let studentsList: StudentUser[] = [];

            if (Array.isArray(response.data)) {
                studentsList = response.data;
            } else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                const dataProperty = response.data.data as StudentUser[];
                studentsList = dataProperty;
            }

            state.value.myStudents = studentsList;
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to fetch students';
            state.value.myStudents = [];
        } finally {
            state.value.loading = false;
        }
    }

    async function fetchPendingInvitations(): Promise<void> {
        state.value.loading = true;
        state.value.error = null;

        try {
            const response = await api.get<GenericResponse<Invitation[]>>('/invitations/pending');

            let invitationsList: Invitation[] = [];

            if (Array.isArray(response.data)) {
                invitationsList = response.data;
            } else if (response.data && typeof response.data === 'object' && 'data' in response.data) {
                const dataProperty = response.data.data as Invitation[];
                invitationsList = dataProperty;
            }

            state.value.pendingInvitations = invitationsList;
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to fetch invitations';
            state.value.pendingInvitations = [];
        } finally {
            state.value.loading = false;
        }
    }

    async function acceptInvitation(invitationId: number): Promise<void> {
        state.value.loading = true;
        state.value.error = null;

        try {
            await api.post(`/invitations/${invitationId}/accept`);

            state.value.pendingInvitations = state.value.pendingInvitations.filter((inv) => inv.id !== invitationId);

            // Refetch instructors after accepting invitation
            await fetchMyInstructors();
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to accept invitation';
        } finally {
            state.value.loading = false;
        }
    }

    async function rejectInvitation(invitationId: number): Promise<void> {
        state.value.loading = true;
        state.value.error = null;

        try {
            await api.post(`/invitations/${invitationId}/reject`);

            state.value.pendingInvitations = state.value.pendingInvitations.filter((inv) => inv.id !== invitationId);
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to reject invitation';
        } finally {
            state.value.loading = false;
        }
    }

    async function createInvitation(payload: CreateInvitationPayload): Promise<void> {
        state.value.loading = true;
        state.value.error = null;

        try {
            await api.post('/invitations', payload);

            // Refetch pending invitations after creating one
            await fetchPendingInvitations();
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to create invitation';
        } finally {
            state.value.loading = false;
        }
    }

    async function revokeLink(linkId: number): Promise<void> {
        state.value.loading = true;
        state.value.error = null;

        try {
            await api.post(`/instructor-links/${linkId}/revoke`);

            state.value.myInstructors = state.value.myInstructors.filter((link) => link.id !== linkId);

            // If revoked link was active, clear active instructor
            if (state.value.activeInstructorId === linkId) {
                state.value.activeInstructorId = null;
                clearStorage();
            }
        } catch (err) {
            state.value.error = err instanceof Error ? err.message : 'Failed to revoke link';
        } finally {
            state.value.loading = false;
        }
    }

    function clearInstructor(): void {
        state.value.activeInstructorId = null;
        state.value.myInstructors = [];
        state.value.myStudents = [];
        state.value.pendingInvitations = [];
        state.value.error = null;
        clearStorage();
    }

    async function initialize(): Promise<void> {
        loadFromStorage();
        await fetchMyInstructors();
    }

    return {
        // State
        activeInstructorId,
        myInstructors,
        myStudents,
        pendingInvitations,
        loading,
        error,

        // Getters
        getActiveInstructor,
        getInstructorName,
        getInstructorEmail,
        getPendingInvitationCount,

        // Actions
        setActiveInstructor,
        fetchMyInstructors,
        fetchMyStudents,
        fetchPendingInvitations,
        acceptInvitation,
        rejectInvitation,
        createInvitation,
        revokeLink,
        clearInstructor,
        initialize,
        loadFromStorage,
    };
});

// Export type for use in components
export type InstructorStore = ReturnType<typeof useInstructorStore>;
