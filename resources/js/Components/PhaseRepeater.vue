<script setup>
import { ref } from 'vue';
import { PhTrash, PhPlus } from '@phosphor-icons/vue';

const props = defineProps(['modelValue']);
const emit = defineEmits(['update:modelValue']);

// Liste de modèles prédéfinis
const templates = [
    { name: 'Période de Stage', code: 'S', color: '#FEF08A', hours: 7, priority: 20 },
    { name: 'Fermeture Centre', code: 'FC', color: '#D1D5DB', hours: 0, priority: 100 },
    { name: 'Révisions', code: 'R', color: '#BBF7D0', hours: 7, priority: 10 },
];

const addPhase = () => {
    const newPhase = {
        name: 'Nouvelle Phase',
        start_date: '',
        end_date: '',
        hours_per_day: 7,
        code: 'X',
        color: '#E5E7EB',
        priority: 10
    };
    emit('update:modelValue', [...props.modelValue, newPhase]);
};

const applyTemplate = (index, tpl) => {
    const phases = [...props.modelValue];
    phases[index] = { ...phases[index], name: tpl.name, code: tpl.code, color: tpl.color, hours_per_day: tpl.hours, priority: tpl.priority };
    emit('update:modelValue', phases);
};

const removePhase = (index) => {
    const phases = [...props.modelValue];
    phases.splice(index, 1);
    emit('update:modelValue', phases);
};
</script>

<template>
    <div class="space-y-4">
        <div v-for="(phase, index) in modelValue" :key="index" class="p-4 border border-gray-200 rounded-lg bg-gray-50 flex flex-wrap gap-4 items-end animate-fade-in">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-bold text-gray-500 mb-1">Type</label>
                <div class="flex gap-2 mb-2">
                    <button v-for="t in templates" :key="t.name" type="button" @click="applyTemplate(index, t)" class="text-[10px] px-2 py-1 bg-white border rounded hover:bg-gray-100">
                        {{ t.name }}
                    </button>
                </div>
                <input v-model="phase.name" type="text" placeholder="Nom phase" class="w-full p-2 border rounded text-sm" />
            </div>

            <div class="w-32">
                <label class="block text-xs font-bold text-gray-500 mb-1">Début</label>
                <input v-model="phase.start_date" type="date" class="w-full p-2 border rounded text-sm" />
            </div>
            
            <div class="w-32">
                <label class="block text-xs font-bold text-gray-500 mb-1">Fin</label>
                <input v-model="phase.end_date" type="date" class="w-full p-2 border rounded text-sm" />
            </div>

            <div class="w-20">
                <label class="block text-xs font-bold text-gray-500 mb-1">Heures/j</label>
                <input v-model="phase.hours_per_day" type="number" class="w-full p-2 border rounded text-sm" />
            </div>

            <div class="w-16">
                 <label class="block text-xs font-bold text-gray-500 mb-1">Couleur</label>
                 <input v-model="phase.color" type="color" class="w-full h-9 p-0 border rounded cursor-pointer" />
            </div>

            <button type="button" @click="removePhase(index)" class="p-2 text-red-500 hover:bg-red-100 rounded">
                <PhTrash :size="20" />
            </button>
        </div>

        <button type="button" @click="addPhase" class="flex items-center gap-2 text-sm text-blue-600 font-medium hover:text-blue-800">
            <PhPlus :size="16" /> Ajouter une phase
        </button>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>