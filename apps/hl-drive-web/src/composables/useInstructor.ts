import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import { useInstructorStore } from '@/stores/instructor';

export function useInstructor() {
    const instructorStore = useInstructorStore();

    const {
        activeInstructorId,
        myInstructors,
        myStudents,
        pendingInvitations,
        loading,
        error,
    } = storeToRefs(instructorStore);

    const activeInstructor = computed(() => instructorStore.getActiveInstructor());
    const instructorName = computed(() => instructorStore.getInstructorName());
    const instructorEmail = computed(() => instructorStore.getInstructorEmail());
    const pendingInvitationCount = computed(() => instructorStore.getPendingInvitationCount());

    return {
        // Reactive state
        activeInstructorId,
        myInstructors,
        myStudents,
        pendingInvitations,
        loading,
        error,

        // Computed
        activeInstructor,
        instructorName,
        instructorEmail,
        pendingInvitationCount,

        // Actions
        setActiveInstructor: instructorStore.setActiveInstructor,
        fetchMyInstructors: instructorStore.fetchMyInstructors,
        fetchMyStudents: instructorStore.fetchMyStudents,
        fetchPendingInvitations: instructorStore.fetchPendingInvitations,
        acceptInvitation: instructorStore.acceptInvitation,
        rejectInvitation: instructorStore.rejectInvitation,
        createInvitation: instructorStore.createInvitation,
        revokeLink: instructorStore.revokeLink,
        clearInstructor: instructorStore.clearInstructor,
        initialize: instructorStore.initialize,
    };
}
