<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import { PhArrowLeft, PhFloppyDisk, PhGear } from '@phosphor-icons/vue';
import { watch } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps(['training']);

// On extrait les règles existantes (ou valeurs par défaut)
const rules = props.training.scheduling_rules || {};

const form = useForm({
    title: props.training.title,
    duration_hours: props.training.duration_hours,
    internship_weeks: props.training.internship_weeks,
    internship_periods: props.training.internship_periods || 1,
    
    // Initialisation des champs avancés
    first_stage_delay: rules.first_stage_delay_months || 3.5,
    gaps: rules.gaps_between_stages_months || [],
});

// Même logique de surveillance que Create.vue
watch(() => form.internship_periods, (newVal) => {
    const count = parseInt(newVal) || 1;
    const neededGaps = count - 1;

    if (neededGaps > form.gaps.length) {
        for (let i = form.gaps.length; i < neededGaps; i++) {
            form.gaps.push(1.5);
        }
    } else if (neededGaps < form.gaps.length) {
        form.gaps = form.gaps.slice(0, neededGaps);
    }
});

const submit = () => form.put(route('trainings.update', props.training.id));
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-50 py-10 px-4">
            <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-xl overflow-hidden">
                <div class="bg-orange-500 p-6 flex items-center gap-4 text-white">
                    <Link :href="route('trainings.index')" class="hover:bg-orange-600 p-2 rounded-full transition">
                        <PhArrowLeft :size="20"/>
                    </Link>
                    <h1 class="text-xl font-bold">Modifier la formation</h1>
                </div>
    
                <form @submit.prevent="submit" class="p-8 space-y-8">
                    
                    <section class="space-y-4">
                        <h2 class="text-lg font-bold text-gray-800 border-b pb-2">Informations Générales</h2>
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Titre de la formation</label>
                            <input v-model="form.title" type="text" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 h-11" required />
                        </div>
    
                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">H. Centre</label>
                                <input v-model="form.duration_hours" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 h-11" required />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">H. Stage (Semaines)</label>
                                <input v-model="form.internship_weeks" type="number" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500 h-11" required />
                            </div>
                        </div>
                    </section>
    
                    <section class="bg-orange-50 p-6 rounded-xl border border-orange-200 space-y-4">
                        <h2 class="text-lg font-bold text-orange-800 flex items-center gap-2">
                            <PhGear :size="24" /> Configuration des Stages
                        </h2>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Nombre de périodes</label>
                                <select v-model="form.internship_periods" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 h-11">
                                    <option :value="1">1 seule période</option>
                                    <option :value="2">2 périodes</option>
                                    <option :value="3">3 périodes</option>
                                    <option :value="4">4 périodes</option>
                                </select>
                            </div>
    
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Délai avant 1er stage (Mois)</label>
                                <input v-model="form.first_stage_delay" type="number" step="0.1" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 h-11" />
                            </div>
                        </div>
    
                        <div v-if="form.gaps.length > 0" class="mt-4 pt-4 border-t border-orange-200">
                            <h3 class="text-sm font-bold text-gray-900 mb-3">Intervalles entre les stages</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="(gap, index) in form.gaps" :key="index">
                                    <label class="block text-xs font-bold text-gray-600 mb-1">
                                        Entre Stage {{ index + 1 }} et {{ index + 2 }} (Mois)
                                    </label>
                                    <input v-model="form.gaps[index]" type="number" step="0.1" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 h-10" />
                                </div>
                            </div>
                        </div>
                    </section>
    
                    <div class="pt-4 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-bold shadow flex items-center gap-2">
                            <PhFloppyDisk :size="20" /> Mettre à jour
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>