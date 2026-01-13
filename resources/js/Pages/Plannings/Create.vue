<script setup>
import { useForm } from '@inertiajs/vue3';
import PhaseRepeater from '@/Components/PhaseRepeater.vue';
import { PhCalendarPlus, PhSpinner } from '@phosphor-icons/vue';
import gsap from 'gsap';
import { onMounted } from 'vue';

// On retire learner_name de l'objet form
const form = useForm({
    title: '',
    start_date: '',
    end_date: '',
    default_hours: 7,
    phases: []
});

const submit = () => {
    form.post(route('plannings.store'));
};

onMounted(() => {
    gsap.from('.gsap-entry', {
        y: 20, opacity: 0, stagger: 0.1, duration: 0.6, ease: 'power2.out'
    });
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4">
        <div class="max-w-4xl mx-auto bg-white shadow-xl rounded-2xl overflow-hidden">
            <div class="bg-blue-600 p-6 text-white flex items-center justify-between gsap-entry">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <PhCalendarPlus :size="32" /> Nouveau Planning
                    </h1>
                    <p class="text-blue-100 text-sm mt-1">Configurez les dates et les exceptions.</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="p-8 space-y-8">
                
                <section class="gsap-entry">
                    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Informations Générales</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Intitulé Formation</label>
                            <input v-model="form.title" type="text" class="px-3 py-3 w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Ex: TITRE PRO COMPTABLE" required />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Début</label>
                            <input v-model="form.start_date" type="date" class="px-3 py-3 w-full border-gray-300 rounded-lg shadow-sm" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date Fin</label>
                            <input v-model="form.end_date" type="date" class="px-3 py-3 w-full border-gray-300 rounded-lg shadow-sm" required />
                        </div>
                        <div>
                             <label class="block text-sm font-medium text-gray-700 mb-1">Heures / jour (défaut)</label>
                             <input v-model="form.default_hours" type="number" class="px-3 py-3 w-full border-gray-300 rounded-lg shadow-sm" />
                        </div>
                    </div>
                </section>

                <section class="gsap-entry">
                    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Exceptions & Phases</h2>
                    <PhaseRepeater v-model="form.phases" />
                </section>

                <div class="flex justify-end pt-6 border-t gsap-entry">
                    <button type="submit" :disabled="form.processing" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition-transform active:scale-95 flex items-center gap-2">
                        <PhSpinner v-if="form.processing" class="animate-spin" />
                        Générer le planning
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>