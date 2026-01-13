<script setup>
import { useForm } from '@inertiajs/vue3';
import { PhCalendarPlus, PhSpinner, PhMagicWand } from '@phosphor-icons/vue';
import gsap from 'gsap';
import { onMounted } from 'vue';

// On reçoit la liste des formations
const props = defineProps(['trainings']);

const form = useForm({
    training_id: '',
    start_date: '',
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
        <div class="max-w-xl mx-auto bg-white shadow-xl rounded-2xl overflow-hidden">
            
            <div class="bg-indigo-600 p-6 text-white gsap-entry">
                <h1 class="text-2xl font-bold flex items-center gap-2">
                    <PhMagicWand :size="32" /> Générateur Auto
                </h1>
                <p class="text-indigo-100 text-sm mt-1">
                    Sélectionnez la formation, on s'occupe du reste (Stages, Noël, Révisions...).
                </p>
            </div>

            <form @submit.prevent="submit" class="p-8 space-y-6">
                
                <div class="gsap-entry">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Formation</label>
                    <select v-model="form.training_id" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 h-12 text-lg" required>
                        <option value="" disabled>-- Choisir une formation --</option>
                        <option v-for="t in trainings" :key="t.id" :value="t.id">
                            {{ t.title }} ({{ t.duration_hours }}h)
                        </option>
                    </select>
                </div>

                <div class="gsap-entry">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Date de démarrage</label>
                    <input v-model="form.start_date" type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-12" required />
                </div>

                <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-sm text-blue-700 gsap-entry">
                    <p class="font-bold mb-1">Règles appliquées automatiquement :</p>
                    <ul class="list-disc list-inside space-y-1 ml-1 text-xs">
                        <li>Journée type : 7h</li>
                        <li>Stage : Après 4 mois</li>
                        <li>Révisions : 2 dernières semaines</li>
                        <li>Fermeture : Noël (23/12 - 05/01)</li>
                    </ul>
                </div>

                <div class="pt-4 gsap-entry">
                    <button type="submit" :disabled="form.processing" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-4 rounded-xl font-bold shadow-lg transition-transform active:scale-95 flex items-center justify-center gap-2 text-lg">
                        <PhSpinner v-if="form.processing" class="animate-spin" />
                        <span v-else>Générer le Planning</span>
                    </button>
                </div>

            </form>
        </div>
    </div>
</template>