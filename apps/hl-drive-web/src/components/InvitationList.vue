<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useInstructor } from '@/composables/useInstructor';
import { useI18n } from 'vue-i18n';
import { formatDate } from '@/utils/date';

const { t } = useI18n();
const { pendingInvitations, loading, acceptInvitation, rejectInvitation } = useInstructor();

const loadingInvitationId = computed(() => 0);

async function handleAccept(invitationId: number): Promise<void> {
    await acceptInvitation(invitationId);
}

async function handleReject(invitationId: number): Promise<void> {
    await rejectInvitation(invitationId);
}
</script>

<template>
    <div class="space-y-3">
        <div
            v-if="pendingInvitations.length === 0"
            class="text-center py-8 text-gray-500"
        >
            <Icon icon="fa7-regular:envelope" class="w-8 h-8 mx-auto mb-2 opacity-50" />
            <p>{{ t('invitation.no_pending') }}</p>
        </div>

        <div
            v-for="invitation in pendingInvitations"
            :key="invitation.id"
            class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow"
        >
            <!-- Header -->
            <div class="flex items-start justify-between mb-3">
                <div>
                    <p class="font-medium text-gray-900">{{ invitation.email }}</p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ t('invitation.from') }}: {{ invitation.inviter?.name }}
                    </p>
                </div>
                <span
                    class="inline-block px-2 py-1 rounded text-xs font-medium"
                    :class="{
                        'bg-yellow-100 text-yellow-800': invitation.status === 'PENDING',
                        'bg-green-100 text-green-800': invitation.status === 'ACCEPTED',
                        'bg-red-100 text-red-800': invitation.status === 'REJECTED',
                    }"
                >
                    {{ t(`invitation.status.${invitation.status.toLowerCase()}`) }}
                </span>
            </div>

            <!-- Expiration -->
            <div class="mb-4 text-xs text-gray-500">
                {{ t('invitation.expires') }}: {{ formatDate(invitation.expires_at, 'br-date') }}
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <button
                    :disabled="loading || invitation.status !== 'PENDING'"
                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium bg-green-600 text-white hover:bg-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleAccept(invitation.id)"
                >
                    <Icon
                        v-if="loading"
                        icon="mdi:loading"
                        class="w-4 h-4 animate-spin"
                    />
                    <Icon
                        v-else
                        icon="fa7-solid:check"
                        class="w-4 h-4"
                    />
                    {{ t('invitation.accept') }}
                </button>

                <button
                    :disabled="loading || invitation.status !== 'PENDING'"
                    class="flex-1 flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-medium bg-gray-200 text-gray-800 hover:bg-gray-300 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    @click="handleReject(invitation.id)"
                >
                    <Icon
                        v-if="loading"
                        icon="mdi:loading"
                        class="w-4 h-4 animate-spin"
                    />
                    <Icon
                        v-else
                        icon="fa7-solid:times"
                        class="w-4 h-4"
                    />
                    {{ t('invitation.reject') }}
                </button>
            </div>
        </div>
    </div>
</template>
