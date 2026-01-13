<script setup>
import { Link, router } from '@inertiajs/vue3';
import { PhGraduationCap, PhPlus, PhPencilSimple, PhTrash } from '@phosphor-icons/vue';

defineProps(['trainings']);

const destroy = (id) => {
    if (confirm('Supprimer cette formation du catalogue ?')) {
        router.delete(route('trainings.destroy', id));
    }
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4">
        <div class="max-w-5xl mx-auto">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <PhGraduationCap :size="32" class="text-indigo-600"/> Catalogue Formations
                    </h1>
                    <p class="text-sm text-gray-500">Gérez les durées et les périodes de stage.</p>
                </div>
                <Link :href="route('trainings.create')" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 shadow transition">
                    <PhPlus weight="bold"/> Nouvelle Formation
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow border border-gray-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-6 py-4 font-semibold">Intitulé</th>
                            <th class="px-6 py-4 font-semibold text-center">Durée (Heures)</th>
                            <th class="px-6 py-4 font-semibold text-center">Stage (Semaines)</th>
                            <th class="px-6 py-4 font-semibold text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="t in trainings" :key="t.id" class="hover:bg-gray-50 group">
                            <td class="px-6 py-4 font-medium text-gray-900">{{ t.title }}</td>
                            <td class="px-6 py-4 text-center text-gray-600">
                                <span class="bg-indigo-50 text-indigo-700 px-2 py-1 rounded text-xs font-bold">{{ t.duration_hours }} h</span>
                            </td>
                            <td class="px-6 py-4 text-center text-gray-600">
                                <span class="bg-yellow-50 text-yellow-700 px-2 py-1 rounded text-xs font-bold">{{ t.internship_weeks }} sem.</span>
                            </td>
                            <td class="px-6 py-4 text-right flex justify-end gap-2">
                                <Link :href="route('trainings.edit', t.id)" class="p-2 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition">
                                    <PhPencilSimple :size="20"/>
                                </Link>
                                <button @click="destroy(t.id)" class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded transition">
                                    <PhTrash :size="20"/>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="trainings.length === 0">
                            <td colspan="4" class="px-6 py-8 text-center text-gray-400 italic">Aucune formation dans le catalogue.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>