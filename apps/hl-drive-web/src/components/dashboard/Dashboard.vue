<script setup lang="ts">
import { computed, ref } from 'vue';
import { useWallets } from '@/composables/useWallets';
import WalletBalance from './WalletBalance.vue';
import TransactionHistory from './TransactionHistory.vue';
import QuickStats from './QuickStats.vue';
import LessonsList from '../lessons/LessonsList.vue';
import PackageList from '../packages/PackageList.vue';
import type { Wallet } from '@/types';

interface Props {
    wallets?: Wallet[];
    studentId?: number;
    instructorId?: number;
    activeTab?: 'overview' | 'packages' | 'lessons';
}

const props = withDefaults(defineProps<Props>(), {
    activeTab: 'overview',
});

const activeTab = ref<Props['activeTab']>(props.activeTab);
const selectedWallet = ref<Wallet | undefined>(props.wallets?.[0]);

const { balance } = useWallets();
</script>

<template>
    <div class="space-y-8">
        <!-- Header -->
        <div>
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-2">Dashboard</h1>
            <p class="text-gray-600 dark:text-gray-400">Gerencie suas aulas, pacotes e saldo de horas</p>
        </div>

        <!-- Tabs -->
        <div class="flex gap-4 border-b border-gray-300 dark:border-gray-700">
            <button
                @click="activeTab = 'overview'"
                :class="[
                    'px-4 py-3 font-medium border-b-2 transition-colors',
                    activeTab === 'overview'
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300',
                ]"
            >
                Visão Geral
            </button>
            <button
                @click="activeTab = 'packages'"
                :class="[
                    'px-4 py-3 font-medium border-b-2 transition-colors',
                    activeTab === 'packages'
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300',
                ]"
            >
                Pacotes
            </button>
            <button
                @click="activeTab = 'lessons'"
                :class="[
                    'px-4 py-3 font-medium border-b-2 transition-colors',
                    activeTab === 'lessons'
                        ? 'border-blue-600 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300',
                ]"
            >
                Aulas
            </button>
        </div>

        <!-- Overview Tab -->
        <div v-if="activeTab === 'overview'" class="space-y-8">
            <!-- Quick Stats -->
            <QuickStats :student-id="studentId" :instructor-id="instructorId" :wallet-balance="balance" />

            <!-- Wallet Selection -->
            <div v-if="wallets && wallets.length > 0" class="space-y-4">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Selecione uma Carteira</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button
                        v-for="wallet in wallets"
                        :key="wallet.id"
                        @click="selectedWallet = wallet"
                        :class="[
                            'p-4 rounded-lg border-2 transition-colors text-left',
                            selectedWallet?.id === wallet.id
                                ? 'border-blue-600 bg-blue-50 dark:bg-blue-900/20'
                                : 'border-gray-300 dark:border-gray-600 hover:border-blue-400',
                        ]"
                    >
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ wallet.name }}</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">{{ wallet.description }}</p>
                    </button>
                </div>
            </div>

            <!-- Wallet Balance -->
            <div v-if="selectedWallet">
                <WalletBalance :wallet="selectedWallet" />
            </div>

            <!-- Transaction History -->
            <TransactionHistory />
        </div>

        <!-- Packages Tab -->
        <div v-if="activeTab === 'packages'" class="space-y-8">
            <PackageList />
        </div>

        <!-- Lessons Tab -->
        <div v-if="activeTab === 'lessons'" class="space-y-8">
            <LessonsList :student-id="studentId" :instructor-id="instructorId" />
        </div>
    </div>
</template>
