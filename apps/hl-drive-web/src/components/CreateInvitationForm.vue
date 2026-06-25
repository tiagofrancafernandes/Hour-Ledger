<script setup lang="ts">
import { ref } from 'vue';
import { Icon } from '@iconify/vue';
import { useInstructor } from '@/composables/useInstructor';
import { useI18n } from 'vue-i18n';
import { useToast } from '@/composables/useToast';
import type { CreateInvitationPayload } from '@/types/instructor';
import { UserRole as UserRoleValues } from '@/types/instructor';

const { t } = useI18n();
const toast = useToast();
const { loading, createInvitation } = useInstructor();

const email = ref('');
const isSubmitting = ref(false);

function isEmailValid(emailInput: string): boolean {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    return emailRegex.test(emailInput);
}

async function handleSubmit(): Promise<void> {
    if (!email.value.trim()) {
        toast.error(t('invitation.email_required'));

        return;
    }

    if (!isEmailValid(email.value)) {
        toast.error(t('invitation.email_invalid'));

        return;
    }

    isSubmitting.value = true;

    try {
        const payload: CreateInvitationPayload = {
            email: email.value,
            role: UserRoleValues.STUDENT,
        };

        await createInvitation(payload);
        email.value = '';
        toast.success(t('invitation.created_success'));
    } catch (err) {
        const errorMessage = err instanceof Error ? err.message : t('invitation.create_error');
        toast.error(errorMessage);
    } finally {
        isSubmitting.value = false;
    }
}

function handleKeyDown(event: KeyboardEvent): void {
    if (event.key === 'Enter' && !isSubmitting.value && !loading.value) {
        handleSubmit();
    }
}
</script>

<template>
    <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">
            {{ t('invitation.create_new') }}
        </h3>

        <div class="space-y-4">
            <!-- Email Input -->
            <div>
                <label
                    for="email"
                    class="block text-sm font-medium text-gray-700 mb-2"
                >
                    {{ t('invitation.email') }}
                </label>
                <input
                    id="email"
                    v-model="email"
                    type="email"
                    :placeholder="t('invitation.email_placeholder')"
                    :disabled="isSubmitting || loading"
                    class="w-full px-4 py-2 rounded-lg border border-gray-300 text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed"
                    @keydown="handleKeyDown"
                />
            </div>

            <!-- Submit Button -->
            <button
                :disabled="isSubmitting || loading || !email.trim()"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                @click="handleSubmit"
            >
                <Icon
                    v-if="isSubmitting || loading"
                    icon="mdi:loading"
                    class="w-4 h-4 animate-spin"
                />
                <Icon
                    v-else
                    icon="fa7-solid:paper-plane"
                    class="w-4 h-4"
                />
                {{ t('invitation.send') }}
            </button>
        </div>
    </div>
</template>
