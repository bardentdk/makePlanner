<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { PhArrowLeft, PhFloppyDisk, PhGear } from '@phosphor-icons/vue';
import { watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const form = useForm({
    title: '',
    duration_hours: '',
    internship_weeks: '',
    internship_periods: 1,
    
    // Nouveaux champs pour la configuration avancée
    first_stage_delay: 3.5, // Par défaut
    gaps: [], // Tableau des intervalles
});

// Surveille le nombre de périodes pour ajuster les champs d'intervalles
watch(() => form.internship_periods, (newVal) => {
    const count = parseInt(newVal) || 1;
    const neededGaps = count - 1; // Si 3 stages, on a besoin de 2 intervalles

    // On ajuste la taille du tableau gaps
    if (neededGaps > form.gaps.length) {
        // On ajoute des cases (par défaut 1.5 mois)
        for (let i = form.gaps.length; i < neededGaps; i++) {
            form.gaps.push(1.5);
        }
    } else if (neededGaps < form.gaps.length) {
        // On retire les cases en trop
        form.gaps = form.gaps.slice(0, neededGaps);
    }
});

const submit = () => form.post(route('trainings.store'));
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 py-10 px-4">
            <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="bg-indigo-600 p-6 flex items-center gap-4 text-white">
                    <Link :href="route('trainings.index')" class="hover:bg-indigo-700 p-2 rounded-full transition">
                        <PhArrowLeft :size="20"/>
                    </Link>
                    <h1 class="text-xl font-bold">Ajouter une formation</h1>
                </div>
    
                <form @submit.prevent="submit" class="p-8 space-y-8">
                    
                    <section class="space-y-4">
                        <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Informations Générales</h2>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Titre de la formation</label>
                            <input v-model="form.title" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11" placeholder="Ex: TP COMPTABLE..." required />
                            <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                        </div>
    
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">H. Centre (Formation)</label>
                                <input v-model="form.duration_hours" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11" required />
                                <div v-if="form.errors.duration_hours" class="text-red-500 text-sm mt-1">{{ form.errors.duration_hours }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">H. Stage (Total)</label>
                                <input v-model="form.internship_weeks" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11" placeholder="Ex: 6 semaines" required />
                                <p class="text-xs text-gray-500 mt-1">Nombre total de semaines.</p>
                                <div v-if="form.errors.internship_weeks" class="text-red-500 text-sm mt-1">{{ form.errors.internship_weeks }}</div>
                            </div>
                        </div>
                    </section>
    
                    <section class="bg-gray-50 p-6 rounded-xl border border-gray-200 space-y-4">
                        <h2 class="text-lg font-bold text-indigo-700 flex items-center gap-2">
                            <PhGear :size="24" /> Configuration des Stages
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nombre de périodes</label>
                                <select v-model="form.internship_periods" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11">
                                    <option :value="1">1 seule période</option>
                                    <option :value="2">2 périodes</option>
                                    <option :value="3">3 périodes</option>
                                    <option :value="4">4 périodes</option>
                                </select>
                            </div>
    
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Délai avant 1er stage (Mois)</label>
                                <input v-model="form.first_stage_delay" type="number" step="0.1" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-11" />
                                <p class="text-xs text-gray-500 mt-1">Ex: 2.5 pour 2 mois et demi après le début.</p>
                            </div>
                        </div>
    
                        <div v-if="form.gaps.length > 0" class="mt-4 pt-4 border-t border-gray-200">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">Intervalles entre les stages</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="(gap, index) in form.gaps" :key="index">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">
                                        Entre Stage {{ index + 1 }} et {{ index + 2 }} (Mois)
                                    </label>
                                    <input v-model="form.gaps[index]" type="number" step="0.1" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-10" />
                                </div>
                            </div>
                        </div>
                    </section>
    
                    <div class="pt-4 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-lg font-bold shadow flex items-center gap-2">
                            <PhFloppyDisk :size="20" /> Enregistrer la formation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>