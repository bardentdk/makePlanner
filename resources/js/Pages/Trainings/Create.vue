<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { PhArrowLeft, PhFloppyDisk } from '@phosphor-icons/vue';

const form = useForm({
    title: '',
    duration_hours: 800, // Valeur par défaut courante
    internship_weeks: 4, // Valeur par défaut courante
});

const submit = () => form.post(route('trainings.store'));
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4">
        <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="bg-indigo-600 p-6 flex items-center gap-4 text-white">
                <Link :href="route('trainings.index')" class="hover:bg-indigo-700 p-2 rounded-full transition">
                    <PhArrowLeft :size="20"/>
                </Link>
                <h1 class="text-xl font-bold">Ajouter une formation</h1>
            </div>

            <form @submit.prevent="submit" class="p-8 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Titre de la formation</label>
                    <input v-model="form.title" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11" placeholder="Ex: TP COMPTABLE..." required />
                    <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Durée Totale (Heures)</label>
                        <input v-model="form.duration_hours" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11" required />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Durée Stage (Semaines)</label>
                        <input v-model="form.internship_weeks" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11" required />
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-bold shadow flex items-center gap-2">
                        <PhFloppyDisk :size="20" /> Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>