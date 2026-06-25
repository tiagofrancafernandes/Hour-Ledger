import { useInstructorStore } from '@/stores/instructor';

export interface InstructorHeadersComposable {
    headers: () => Record<string, string>;
    getInstructorIdHeader: () => string | null;
}

export function useInstructorHeaders(): InstructorHeadersComposable {
    const instructorStore = useInstructorStore();

    function getInstructorIdHeader(): string | null {
        const activeInstructorId = instructorStore.activeInstructorId;

        if (!activeInstructorId) {
            return null;
        }

        return String(activeInstructorId);
    }

    function headers(): Record<string, string> {
        const headerRecord: Record<string, string> = {};
        const instructorHeader = getInstructorIdHeader();

        if (instructorHeader) {
            headerRecord['X-Instructor-ID'] = instructorHeader;
        }

        return headerRecord;
    }

    return {
        headers,
        getInstructorIdHeader,
    };
}
