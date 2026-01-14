<script setup>
import { Link } from '@inertiajs/vue3';
import { PhFileXls, PhArrowLeft, PhFilePdf, PhPencilSimple} from '@phosphor-icons/vue';
import gsap from 'gsap';
import { onMounted } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps(['planning', 'grid']);

onMounted(() => {
    // Animation d'entrée des colonnes de mois
    gsap.from('.month-column', {
        y: 50,
        opacity: 0,
        stagger: 0.1,
        duration: 0.8,
        ease: 'back.out(1.7)'
    });
});
</script>

<template>
    <AppLayout>
        <div class="min-h-screen bg-gray-100 flex flex-col">
            <header class="bg-white shadow z-10 p-4 sticky top-0">
                <div class="max-w-7xl mx-auto flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <Link :href="route('plannings.index')" class="text-gray-500 hover:text-gray-800 transition">
                            <PhArrowLeft :size="24" />
                        </Link>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">{{ planning.title }}</h1>
                            <p class="text-xs text-gray-500">Pour : {{ planning.learner_name }}</p>
                        </div>
                    </div>
                    <!-- <Link :href="route('plannings.edit', planning.id)" class="text-gray-500 hover:text-blue-600 bg-white hover:bg-blue-50 border border-gray-200 p-2 rounded-lg transition-colors" title="Modifier">
                        <PhPencilSimple :size="20" weight="bold" />
                    </Link>
                    <a :href="route('plannings.export', planning.id)" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 font-medium shadow transition hover:-translate-y-0.5">
                        <PhFileXls :size="20" /> Exporter Excel
                    </a>
                    <a :href="route('plannings.pdf', planning.id)" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 font-medium shadow transition hover:-translate-y-0.5 ml-2">
                        <PhFilePdf :size="20" /> PDF
                    </a> -->
                    <div class="flex items-center gap-2">
                        <Link :href="route('plannings.edit', planning.id)" class="text-gray-500 hover:text-blue-600 bg-white hover:bg-blue-50 border border-gray-200 p-2 rounded-lg transition-colors" title="Modifier">
                            <PhPencilSimple :size="20" weight="bold" />
                        </Link>
    
                        <div class="w-px h-6 bg-gray-300 mx-2"></div>
    
                        <a :href="route('plannings.export', planning.id)" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 font-medium shadow transition hover:-translate-y-0.5">
                            <PhFileXls :size="20" /> Exporter Excel
                        </a>
                        <a :href="route('plannings.pdf', planning.id)" target="_blank" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 font-medium shadow transition hover:-translate-y-0.5 ml-2">
                            <PhFilePdf :size="20" /> PDF
                        </a>
                    </div>
                </div>
            </header>
    
            <main class="flex-1 overflow-x-auto p-8">
                <div class="inline-flex gap-4 min-w-full pb-8">
                    
                    <div v-for="(month, key) in grid" :key="key" class="month-column bg-white rounded-lg shadow-sm border border-gray-200 w-[180px] flex-shrink-0 flex flex-col">
                        <div class="bg-gray-100 p-2 text-center font-bold text-sm border-b border-gray-200 text-gray-700 uppercase">
                            {{ month.month_label }}
                        </div>
                        
                        <div class="grid grid-cols-3 text-[10px] text-gray-500 font-semibold border-b border-gray-200">
                            <div class="p-1 text-center border-r">J</div>
                            <div class="p-1 text-center border-r">S</div>
                            <div class="p-1 text-center">H</div>
                        </div>
    
                        <div class="flex-1 overflow-y-auto custom-scrollbar">
                            <div v-for="day in month.days" :key="day.date" class="grid grid-cols-3 text-xs border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors h-8 items-center">
                                <div class="text-center text-gray-400 font-mono">{{ new Date(day.date).getDate() }}</div>
                                <div class="text-center font-bold" :class="day.type === 'weekend' ? 'text-gray-300' : 'text-gray-600'">
                                    {{ day.dayLetter }}
                                </div>
                                <div class="text-center font-bold h-full flex items-center justify-center text-[10px]"
                                     :style="{ backgroundColor: day.color, color: day.color === '#FFFFFF' ? '#000' : '#000' }">
                                    {{ day.content }}
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-2 border-t text-[10px] text-gray-400 text-center">
                            Total calculé dans l'export
                        </div>
                    </div>
    
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<style>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 4px;
}
</style>