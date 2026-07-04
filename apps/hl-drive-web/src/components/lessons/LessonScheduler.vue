<script setup lang="ts">
import { ref, computed } from 'vue';
import { useLessons } from '@/composables/useLessons';
import type { LessonForm } from '@/types';

interface Props {
    studentId?: number;
    instructorId?: number;
    walletId: number;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'lesson-created': [];
}>();

const { createLesson, loading, error } = useLessons();

const selectedDate = ref<string>('');
const selectedTime = ref<string>('09:00');
const durationMinutes = ref<number>(60);
const notes = ref<string>('');
const validationErrors = ref<Record<string, string>>({});

const minDate = computed(() => {
    const today = new Date();
    return today.toISOString().split('T')[0];
});

const validateForm = (): boolean => {
    validationErrors.value = {};

    if (!selectedDate.value) {
        validationErrors.value.date = 'Data é obrigatória';
    }

    if (!selectedTime.value) {
        validationErrors.value.time = 'Horário é obrigatório';
    }

    if (durationMinutes.value <= 0) {
        validationErrors.value.duration = 'Duração deve ser maior que 0';
    }

    return Object.keys(validationErrors.value).length === 0;
};

const handleSchedule = async () => {
    if (!validateForm()) {
        return;
    }

    const [year, month, day] = selectedDate.value.split('-');
    const dateTime = new Date(`${year}-${month}-${day}T${selectedTime.value}:00`).toISOString();

    const formData: LessonForm = {
        student_id: props.studentId || 0,
        wallet_id: props.walletId,
        scheduled_at: dateTime,
        duration_minutes: durationMinutes.value,
        notes: notes.value || undefined,
    };

    try {
        await createLesson(formData);

        selectedDate.value = '';
        selectedTime.value = '09:00';
        durationMinutes.value = 60;
        notes.value = '';

        emit('lesson-created');
    } catch (err) {
        // Error is handled by composable
    }
};
</script>

<template>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Agendar Aula</h2>

        <form @submit.prevent="handleSchedule" class="space-y-6">
            <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-700 rounded-lg">
                <p class="text-red-800 dark:text-red-200">{{ error }}</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Data <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="selectedDate"
                        type="date"
                        :min="minDate"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <p v-if="validationErrors.date" class="text-red-500 text-sm mt-1">{{ validationErrors.date }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Horário <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="selectedTime"
                        type="time"
                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <p v-if="validationErrors.time" class="text-red-500 text-sm mt-1">{{ validationErrors.time }}</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Duração (minutos) <span class="text-red-500">*</span>
                </label>
                <select
                    v-model.number="durationMinutes"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    <option value="30">30 minutos</option>
                    <option value="45">45 minutos</option>
                    <option value="60">1 hora</option>
                    <option value="90">1 hora e 30 minutos</option>
                    <option value="120">2 horas</option>
                </select>
                <p v-if="validationErrors.duration" class="text-red-500 text-sm mt-1">{{ validationErrors.duration }}</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"> Observações </label>
                <textarea
                    v-model="notes"
                    placeholder="Anotações sobre a aula"
                    rows="3"
                    class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
            </div>

            <button
                type="submit"
                :disabled="loading"
                class="w-full px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition-colors font-medium"
            >
                {{ loading ? 'Agendando...' : 'Agendar Aula' }}
            </button>
        </form>
    </div>
</template>
