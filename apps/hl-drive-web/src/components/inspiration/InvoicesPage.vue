<template>
    <div class="max-w-6xl mx-auto px-6 py-10 space-y-8">
        <!-- Summary cards -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <p class="text-sm text-gray-500">Total Revenue</p>
                <p class="mt-2 text-2xl font-medium">$198,540.23</p>
                <p class="mt-1 text-sm text-gray-500">Last 12 months</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <p class="text-sm text-gray-500">Pending Amount</p>
                <p class="mt-2 text-2xl font-medium">$1.99</p>
                <p class="mt-1 text-sm text-gray-500">1 invoice pending</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <p class="text-sm text-gray-500">Paid Invoices</p>
                <p class="mt-2 text-2xl font-medium">24</p>
                <p class="mt-1 text-sm text-gray-500">Last 30 days</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <p class="text-sm text-gray-500">Overdue</p>
                <p class="mt-2 text-2xl font-medium">1</p>
                <p class="mt-1 text-sm text-gray-500">Requires action</p>
            </div>
        </section>

        <!-- Transactions table -->
        <section class="bg-white border border-gray-200 rounded-lg">
            <!-- Header -->
            <div
                class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 px-6 py-4 border-b border-gray-200"
            >
                <h2 class="text-lg font-medium">Invoices</h2>

                <div class="flex items-center gap-3">
                    <select
                        class="text-sm border border-gray-200 rounded-md px-3 py-1.5 bg-white focus:outline-none focus:ring-2 focus:ring-gray-200"
                    >
                        <option>All statuses</option>
                        <option>Pending</option>
                        <option>Paid</option>
                        <option>Canceled</option>
                    </select>

                    <input
                        type="text"
                        placeholder="Search invoice or item"
                        class="text-sm border border-gray-200 rounded-md px-3 py-1.5 w-64 focus:outline-none focus:ring-2 focus:ring-gray-200"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left font-medium">Invoice</th>
                            <th class="px-6 py-3 text-left font-medium">Items</th>
                            <th class="px-6 py-3 text-left font-medium">Due Date</th>
                            <th class="px-6 py-3 text-left font-medium">Payment Method</th>
                            <th class="px-6 py-3 text-right font-medium">Amount</th>
                            <th class="px-6 py-3 text-left font-medium">Status</th>
                            <th class="px-6 py-3 text-right font-medium">Action</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium">#000001</td>
                            <td class="px-6 py-4">AI Website Builder</td>
                            <td class="px-6 py-4">02/01/2026</td>
                            <td class="px-6 py-4">Instant Transfer</td>
                            <td class="px-6 py-4 text-right">$1.99</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"
                                >
                                    Overdue
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="px-3 py-1.5 text-sm rounded-md bg-gray-900 text-white hover:bg-gray-800">
                                    Pay
                                </button>
                            </td>
                        </tr>

                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium">#000002</td>
                            <td class="px-6 py-4">Subscription Plan M, Security Add-on</td>
                            <td class="px-6 py-4">02/01/2026</td>
                            <td class="px-6 py-4">Credit Card</td>
                            <td class="px-6 py-4 text-right">$54.38</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                >
                                    Paid
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <button class="text-gray-600 hover:underline">Receipt</button>
                                <button class="text-gray-600 hover:underline">Invoice</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 px-6 py-4 border-t border-gray-200"
            >
                <p class="text-sm text-gray-500">
                    Showing
                    <span class="font-medium text-gray-900">1</span>
                    to
                    <span class="font-medium text-gray-900">10</span>
                    of
                    <span class="font-medium text-gray-900">42</span>
                    results
                </p>

                <div class="flex items-center gap-1">
                    <button
                        class="px-3 py-1.5 text-sm rounded-md border border-gray-200 text-gray-400 cursor-not-allowed"
                    >
                        Previous
                    </button>

                    <button class="px-3 py-1.5 text-sm rounded-md bg-gray-900 text-white">1</button>

                    <button
                        class="px-3 py-1.5 text-sm rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50"
                    >
                        2
                    </button>

                    <button
                        class="px-3 py-1.5 text-sm rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50"
                    >
                        3
                    </button>

                    <button
                        class="px-3 py-1.5 text-sm rounded-md border border-gray-200 text-gray-700 hover:bg-gray-50"
                    >
                        Next
                    </button>
                </div>
            </div>
        </section>
    </div>
</template>

<script setup lang="ts"></script>
