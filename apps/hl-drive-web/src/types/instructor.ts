export const InvitationStatus = {
    PENDING: 'PENDING',
    ACCEPTED: 'ACCEPTED',
    REJECTED: 'REJECTED',
} as const;
export type InvitationStatus = (typeof InvitationStatus)[keyof typeof InvitationStatus];

export const InstructorStudentLinkStatus = {
    ACTIVE: 'ACTIVE',
    REVOKED: 'REVOKED',
} as const;
export type InstructorStudentLinkStatus = (typeof InstructorStudentLinkStatus)[keyof typeof InstructorStudentLinkStatus];

export const UserRole = {
    INSTRUCTOR: 'INSTRUCTOR',
    STUDENT: 'STUDENT',
    ADMIN: 'ADMIN',
} as const;
export type UserRole = (typeof UserRole)[keyof typeof UserRole];

export const AccessLevel = {
    FULL: 'FULL',
    READ_ONLY: 'READ_ONLY',
    LIMITED: 'LIMITED',
} as const;
export type AccessLevel = (typeof AccessLevel)[keyof typeof AccessLevel];

export interface InstructorUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    created_at: string;
    updated_at: string;
}

export interface StudentUser {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    active_instructor_id?: number | null;
    created_at: string;
    updated_at: string;
}

export interface Invitation {
    id: number;
    tenant_id: number;
    inviter_id: number;
    email: string;
    recipient_id?: number | null;
    role: UserRole;
    status: InvitationStatus;
    token?: string;
    expires_at: string;
    created_at: string;
    updated_at: string;
    inviter?: InstructorUser;
}

export interface InstructorStudentLink {
    id: number;
    tenant_id: number;
    instructor_id: number;
    student_id: number;
    status: InstructorStudentLinkStatus;
    access_level: AccessLevel;
    linked_at: string;
    revoked_at?: string | null;
    created_at: string;
    updated_at: string;
    instructor?: InstructorUser;
    student?: StudentUser;
}

export interface InstructorContext {
    activeInstructorId: number | null;
    activeInstructor: InstructorUser | null;
    myInstructors: InstructorUser[];
    myStudents: StudentUser[];
    pendingInvitations: Invitation[];
}

export interface CreateInvitationPayload {
    email: string;
    role?: UserRole;
}

export interface SwitchInstructorPayload {
    instructor_id: number;
}

export interface InvitationResponse {
    data: Invitation;
}

export interface InstructorLinkResponse {
    data: InstructorStudentLink;
}

export interface InstructorListResponse {
    data: InstructorStudentLink[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export function getInvitationStatusLabel(status: InvitationStatus): string {
    const labels: Record<InvitationStatus, string> = {
        PENDING: 'Aguardando Resposta',
        ACCEPTED: 'Aceito',
        REJECTED: 'Rejeitado',
    };

    return labels[status] || status;
}

export function getLinkStatusLabel(status: InstructorStudentLinkStatus): string {
    const labels: Record<InstructorStudentLinkStatus, string> = {
        ACTIVE: 'Ativo',
        REVOKED: 'Revogado',
    };

    return labels[status] || status;
}

export function getUserRoleLabel(role: UserRole): string {
    const labels: Record<UserRole, string> = {
        INSTRUCTOR: 'Instrutor',
        STUDENT: 'Aluno',
        ADMIN: 'Administrador',
    };

    return labels[role] || role;
}

export function isInvitationPending(invitation: Invitation): boolean {
    return invitation.status === InvitationStatus.PENDING;
}

export function isInvitationExpired(invitation: Invitation): boolean {
    const expirationDate = new Date(invitation.expires_at);
    const now = new Date();

    return expirationDate < now;
}

export function isLinkActive(link: InstructorStudentLink): boolean {
    return link.status === InstructorStudentLinkStatus.ACTIVE;
}
