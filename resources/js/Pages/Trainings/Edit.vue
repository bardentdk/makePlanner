<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { PhArrowLeft, PhFloppyDisk } from '@phosphor-icons/vue';

const props = defineProps(['training']);

const form = useForm({
    title: props.training.title,
    duration_hours: props.training.duration_hours,
    internship_weeks: props.training.internship_weeks,
});

const submit = () => form.put(route('trainings.update', props.training.id));
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4">
        <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-xl overflow-hidden">
            <div class="bg-orange-500 p-6 flex items-center gap-4 text-white">
                <Link :href="route('trainings.index')" class="hover:bg-orange-600 p-2 rounded-full transition">
                    <PhArrowLeft :size="20"/>
                </Link>
                <h1 class="text-xl font-bold">Modifier la formation</h1>
            </div>

            <form @submit.prevent="submit" class="p-8 space-y-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Titre de la formation</label>
                    <input v-model="form.title" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 h-11" required />
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Durée Totale (Heures)</label>
                        <input v-model="form.duration_hours" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 h-11" required />
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Durée Stage (Semaines)</label>
                        <input v-model="form.internship_weeks" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 h-11" required />
                    </div>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" :disabled="form.processing" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-bold shadow flex items-center gap-2">
                        <PhFloppyDisk :size="20" /> Mettre à jour
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>