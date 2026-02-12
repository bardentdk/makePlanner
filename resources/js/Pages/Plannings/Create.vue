<script setup>
import { useForm } from '@inertiajs/vue3';
import { PhCalendarPlus, PhSpinner, PhMagicWand, PhSteps, PhClock } from '@phosphor-icons/vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import gsap from 'gsap';
import { onMounted } from 'vue';

// 1. CORRECTION : On assigne les props à une variable pour les utiliser dans le script
const props = defineProps(['trainings']);

const form = useForm({
    training_id: '',
    start_date: '',
    nombre_stages: 1,
    heures_stage: 0, // Ce champ sera envoyé au Controller
});

// 2. LOGIQUE : Quand on change de formation, on calcule les heures par défaut
const onTrainingSelect = () => {
    // On cherche la formation sélectionnée dans la liste
    const t = props.trainings.find(x => x.id === form.training_id);
    
    if (t) {
        // On convertit les semaines en heures (Ex: 10 sem * 35 = 350h)
        // L'utilisateur pourra modifier ce chiffre ensuite s'il veut 200h pile.
        form.heures_stage = t.internship_weeks * 35; 
    }
};

const submit = () => {
    form.post(route('plannings.store'), {
        onSuccess: () => {
            // Optionnel : Reset ou notification
        }
    });
};

onMounted(() => {
    gsap.from('.gsap-entry', {
        y: 20, opacity: 0, stagger: 0.1, duration: 0.6, ease: 'power2.out'
    });
});
</script>

<template>
    <AppLayout>
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
                        <select 
                            v-model="form.training_id" 
                            @change="onTrainingSelect"
                            class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 h-12 text-lg" 
                            required
                        >
                            <option value="" disabled>-- Choisir une formation --</option>
                            <option v-for="t in trainings" :key="t.id" :value="t.id">
                                {{ t.title }} ({{ t.duration_hours }}h - {{ t.internship_weeks }} sem. stage)
                            </option>
                        </select>
                        <p v-if="form.errors.training_id" class="text-red-500 text-xs mt-1">{{ form.errors.training_id }}</p>
                    </div>

                    <div class="gsap-entry">
                        <label class="block text-sm font-bold text-gray-700 mb-2">Date de début</label>
                        <div class="relative">
                            <input v-model="form.start_date" type="date" class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-12 pl-3" required />
                        </div>
                        <p v-if="form.errors.start_date" class="text-red-500 text-xs mt-1">{{ form.errors.start_date }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        
                        <div class="gsap-entry">
                            <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                                <PhSteps /> Nb. Périodes
                            </label>
                            <input 
                                v-model="form.nombre_stages" 
                                type="number" 
                                min="0" 
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:ring-indigo-500 h-12" 
                                required 
                                placeholder="Ex: 1"
                            />
                            <p v-if="form.errors.nombre_stages" class="text-red-500 text-xs mt-1">{{ form.errors.nombre_stages }}</p>
                        </div>

                        <div class="gsap-entry">
                            <label class="block text-sm font-bold text-gray-700 mb-2 flex items-center gap-1">
                                <PhClock /> Total Heures Stage
                            </label>
                            <input 
                                v-model="form.heures_stage" 
                                type="number" 
                                min="0" 
                                class="w-full border-orange-300 bg-orange-50 text-orange-900 font-bold rounded-lg shadow-sm focus:ring-orange-500 h-12" 
                                required 
                            />
                            <p class="text-xs text-gray-500 mt-1">Calculé auto. (35h/sem), modifiable.</p>
                            <p v-if="form.errors.heures_stage" class="text-red-500 text-xs mt-1">{{ form.errors.heures_stage }}</p>
                        </div>

                    </div>

                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-100 text-sm text-blue-700 gsap-entry">
                        <p class="font-bold mb-1">Règles appliquées automatiquement :</p>
                        <ul class="list-disc list-inside space-y-1 ml-1 text-xs">
                            <li>Calcul strict basé sur les heures catalogue.</li>
                            <li>Stages déduits automatiquement.</li>
                            <li>Fériés et Weekends exclus.</li>
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
    </AppLayout>
</template>