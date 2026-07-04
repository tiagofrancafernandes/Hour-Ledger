<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useLessons } from '@/composables/useLessons';
import { useWallets } from '@/composables/useWallets';
import type { Lesson } from '@/types';

interface Props {
    lessonId: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    completed: [];
    cancel: [];
}>();

const { fetchLesson, completeLesson, loading, error } = useLessons();
const { balance: walletBalance } = useWallets();

const lesson = ref<Lesson | null>(null);
const hoursConsumed = ref<number>(0);
const validationErrors = ref<Record<string, string>>({});

onMounted(async () => {
    try {
        lesson.value = await fetchLesson(props.lessonId);
        const durationHours = lesson.value.duration_minutes / 60;
        hoursConsumed.value = parseFloat(durationHours.toFixed(2));
    } catch (err) {
        // Error is handled by composable
    }
});

const validateForm = (): boolean => {
    validationErrors.value = {};

    if (hoursConsumed.value <= 0) {
        validationErrors.value.hours = 'Horas devem ser maior que 0';
    }

    if (walletBalance.value && hoursConsumed.value > parseFloat(walletBalance.value)) {
        validationErrors.value.hours = 'Saldo insuficiente para consumir essas horas';
    }

    return Object.keys(validationErrors.value).length === 0;
};

const handleComplete = async () => {
    if (!validateForm() || !lesson.value) {
        return;
    }

    try {
        await completeLesson(lesson.value.id, {
            wallet_id: lesson.value.wallet_id,
            hours_consumed: hoursConsumed.value,
        });

        emit('completed');
    } catch (err) {
        // Error is handled by composable
    }
};
</script>

<template>
    <div class="space-y-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Marcar Aula como Concluída</h2>

        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
            <p class="text-red-800 dark:text-red-200">{{ error }}</p>
        </div>

        <div v-if="lesson" class="space-y-4">
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Dados da Aula:</p>
                <div class="space-y-1">
                    <p class="text-gray-900 dark:text-white">
                        <span class="font-medium">Data/Hora:</span>
                        {{ new Date(lesson.scheduled_at).toLocaleDateString('pt-BR') }}
                    </p>
                    <p class="text-gray-900 dark:text-white">
                        <span class="font-medium">Duração:</span> {{ lesson.duration_minutes }} minutos
                    </p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Horas Consumidas <span class="text-red-500">*</span>
                </label>
                <input
                    v-model.number="hoursConsumed"
                    type="number"
                    step="0.25"
                    min="0"
                    placeholder="Ex: 1.5"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-green-500"
                />
                <p v-if="validationErrors.hours" class="text-red-500 text-sm mt-1">{{ validationErrors.hours }}</p>
            </div>

            <div class="flex gap-3">
                <button
                    @click="emit('cancel')"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                >
                    Cancelar
                </button>
                <button
                    @click="handleComplete"
                    :disabled="loading"
                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400 transition-colors font-medium"
                >
                    {{ loading ? 'Processando...' : 'Confirmar' }}
                </button>
            </div>
        </div>
    </div>
</template>
