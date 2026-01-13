<script setup>
import { useForm } from '@inertiajs/vue3';
import PhaseRepeater from '@/Components/PhaseRepeater.vue';
import { PhPencilSimple, PhSpinner, PhTrash } from '@phosphor-icons/vue';
import gsap from 'gsap';
import { onMounted } from 'vue';

const props = defineProps(['planning']);

// On initialise le form avec les données reçues du backend
const form = useForm({
    title: props.planning.title,
    start_date: props.planning.start_date, // Format YYYY-MM-DD reçu de Laravel
    end_date: props.planning.end_date,
    default_hours: props.planning.default_hours,
    phases: props.planning.phases || [] // Charge les phases existantes
});

const submit = () => {
    form.put(route('plannings.update', props.planning.id));
};

// Fonction de suppression (avec confirmation simple)
const destroy = () => {
    if (confirm('Êtes-vous sûr de vouloir supprimer définitivement ce planning ?')) {
        form.delete(route('plannings.destroy', props.planning.id));
    }
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
            
            <div class="bg-orange-500 p-6 text-white flex items-center justify-between gsap-entry">
                <div>
                    <h1 class="text-2xl font-bold flex items-center gap-2">
                        <PhPencilSimple :size="32" /> Modifier le Planning
                    </h1>
                    <p class="text-orange-100 text-sm mt-1">Modifiez les dates ou les phases.</p>
                </div>
                
                <button type="button" @click="destroy" class="bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm flex items-center gap-2 transition-colors shadow-sm">
                    <PhTrash :size="18" weight="bold" /> Supprimer
                </button>
            </div>

            <form @submit.prevent="submit" class="p-8 space-y-8">
                
                <section class="gsap-entry">
                    <h2 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Informations Générales</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Intitulé Formation</label>
                            <input v-model="form.title" type="text" class="px-3 py-3 w-full border-gray-300 rounded-lg shadow-sm focus:ring-orange-500 focus:border-orange-500" required />
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

                <div class="flex justify-between pt-6 border-t gsap-entry">
                    <button type="button" @click="$inertia.visit(route('plannings.show', props.planning.id))" class="text-gray-500 hover:text-gray-800 font-medium">
                        Annuler
                    </button>

                    <button type="submit" :disabled="form.processing" class="bg-orange-500 hover:bg-orange-600 text-white px-6 py-3 rounded-lg font-semibold shadow-lg transition-transform active:scale-95 flex items-center gap-2">
                        <PhSpinner v-if="form.processing" class="animate-spin" />
                        Enregistrer les modifications
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>