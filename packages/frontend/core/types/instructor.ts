/**
 * Instructor-Student Link Types
 *
 * Frontend types for invitation and link management
 * with type-safe context handling.
 */

// ============================================
// ENUMS
// ============================================

export enum InvitationStatus {
  PENDING = 'PENDING',
  ACCEPTED = 'ACCEPTED',
  REJECTED = 'REJECTED',
}

export enum LinkStatus {
  ACTIVE = 'ACTIVE',
  SUSPENDED = 'SUSPENDED',
  REVOKED = 'REVOKED',
}

export enum AccessLevel {
  BASIC = 'BASIC',
  FULL = 'FULL',
  CUSTOM = 'CUSTOM',
}

export enum UserRole {
  STUDENT = 'STUDENT',
  INSTRUCTOR = 'INSTRUCTOR',
  BOTH = 'BOTH',
}

// ============================================
// INTERFACES
// ============================================

/**
 * Invitation
 *
 * Email-based invitation from instructor to student
 * with token-based verification.
 */
export interface Invitation {
  id: string;
  instructor_id: string;
  student_id?: string;
  email?: string;
  status: InvitationStatus;
  token: string;
  expires_at: string; // ISO datetime
  accepted_at?: string;
  rejected_at?: string;
  created_at: string;
  updated_at: string;
}

/**
 * InstructorStudentLink
 *
 * Active relationship between instructor and student
 * with access control and audit trail.
 */
export interface InstructorStudentLink {
  id: string;
  instructor_id: string;
  student_id: string;
  status: LinkStatus;
  access_level: AccessLevel;
  invitation_id: string;
  created_at: string;
  updated_at: string;
  revoked_at?: string;
}

/**
 * User with Instructor Context
 *
 * Extended user model with instructor relationship
 * information for context-aware queries.
 */
export interface UserWithInstructor {
  id: string;
  email: string;
  name: string;
  role: UserRole;
  active_instructor_id?: string | null;
  active_instructor?: {
    id: string;
    name: string;
    email: string;
  };
}

/**
 * Instructor Context
 *
 * Request-scoped context representing active
 * instructor for this user session.
 */
export interface InstructorContext {
  user_id: string;
  tenant_id: string;
  active_instructor_id?: string | null;
  access_level?: AccessLevel;
}

/**
 * Invitation Response
 *
 * API response format for invitation operations
 */
export interface InvitationResponse {
  data: Invitation;
  message: string;
}

/**
 * Invitation List Response
 *
 * API response format for listing invitations
 */
export interface InvitationListResponse {
  data: Invitation[];
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  message: string;
}

/**
 * Link Response
 *
 * API response format for link operations
 */
export interface LinkResponse {
  data: InstructorStudentLink;
  message: string;
}

/**
 * Link List Response
 *
 * API response format for listing links
 */
export interface LinkListResponse {
  data: InstructorStudentLink[];
  pagination?: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  message: string;
}

/**
 * Create Invitation Request
 *
 * API request format for creating invitation
 */
export interface CreateInvitationRequest {
  email?: string;
  student_id?: string;
}

/**
 * Switch Instructor Request
 *
 * API request format for changing active instructor
 */
export interface SwitchInstructorRequest {
  instructor_id: string;
}

/**
 * My Instructor Response
 *
 * API response for getting current active instructor
 */
export interface MyInstructorResponse {
  data?: {
    id: string;
    name: string;
    email: string;
  } | null;
  message: string;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

export const isInvitationPending = (invitation: Invitation): boolean => {
  return invitation.status === InvitationStatus.PENDING;
};

export const isInvitationExpired = (invitation: Invitation): boolean => {
  return new Date(invitation.expires_at) < new Date();
};

export const isLinkActive = (link: InstructorStudentLink): boolean => {
  return link.status === LinkStatus.ACTIVE;
};

export const invitationStatusLabel = (status: InvitationStatus): string => {
  const labels: Record<InvitationStatus, string> = {
    [InvitationStatus.PENDING]: 'Aguardando Resposta',
    [InvitationStatus.ACCEPTED]: 'Aceito',
    [InvitationStatus.REJECTED]: 'Rejeitado',
  };
  return labels[status];
};

export const linkStatusLabel = (status: LinkStatus): string => {
  const labels: Record<LinkStatus, string> = {
    [LinkStatus.ACTIVE]: 'Ativo',
    [LinkStatus.SUSPENDED]: 'Suspenso',
    [LinkStatus.REVOKED]: 'Revogado',
  };
  return labels[status];
};

export const accessLevelLabel = (level: AccessLevel): string => {
  const labels: Record<AccessLevel, string> = {
    [AccessLevel.BASIC]: 'Básico (Leitura)',
    [AccessLevel.FULL]: 'Completo',
    [AccessLevel.CUSTOM]: 'Customizado',
  };
  return labels[level];
};

export const userRoleLabel = (role: UserRole): string => {
  const labels: Record<UserRole, string> = {
    [UserRole.STUDENT]: 'Aluno',
    [UserRole.INSTRUCTOR]: 'Instrutor',
    [UserRole.BOTH]: 'Aluno e Instrutor',
  };
  return labels[role];
};
