<script setup>
import { Link } from '@inertiajs/vue3';
import { 
    PhPlus, 
    PhFileXls, 
    PhFilePdf, 
    PhEye, 
    PhCalendarBlank, 
    PhUser,
    PhCaretLeft,
    PhCaretRight
} from '@phosphor-icons/vue';

// On reçoit les plannings depuis le controller (objet paginé Laravel)
const props = defineProps(['plannings']);

// Fonction utilitaire pour formater les dates (FR)
const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: '2-digit', month: 'short', year: 'numeric'
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 py-10 px-4 sm:px-6 lg:px-8">
        
        <div class="max-w-7xl mx-auto flex flex-col sm:flex-row justify-between items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Mes Plannings</h1>
                <p class="text-sm text-gray-500 mt-1">Gérez et exportez vos documents de formation.</p>
            </div>
            
            <Link :href="route('plannings.create')" 
                  class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-lg font-medium shadow-sm transition-all flex items-center gap-2 hover:-translate-y-0.5">
                <PhPlus :size="20" weight="bold" />
                Nouveau Planning
            </Link>
        </div>

        <div class="max-w-7xl mx-auto bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            
            <div v-if="plannings.data.length === 0" class="p-12 text-center text-gray-500">
                <PhCalendarBlank :size="48" class="mx-auto text-gray-300 mb-4" />
                <p class="text-lg font-medium text-gray-900">Aucun planning pour le moment</p>
                <p class="mb-6">Commencez par créer votre premier planning de formation.</p>
                <Link :href="route('plannings.create')" class="text-blue-600 hover:underline font-medium">
                    Créer un planning &rarr;
                </Link>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200 text-xs uppercase text-gray-500 font-semibold tracking-wide">
                            <th class="px-6 py-4">Intitulé Formation</th>
                            <!-- <th class="px-6 py-4">Apprenant</th> -->
                            <th class="px-6 py-4">Période</th>
                            <th class="px-6 py-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="planning in plannings.data" :key="planning.id" class="hover:bg-gray-50 transition-colors group">
                            
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-900">{{ planning.title }}</div>
                                <div class="text-xs text-gray-400 mt-0.5">Créé le {{ formatDate(planning.created_at) }}</div>
                            </td>

                            <!-- <td class="px-6 py-4">
                                <div class="flex items-center gap-2 text-gray-700">
                                    <div class="w-8 h-8 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                                        <PhUser :size="16" weight="fill" />
                                    </div>
                                    <span class="font-medium">{{ planning.learner_name || 'Non renseigné' }}</span>
                                </div>
                            </td> -->

                            <td class="px-6 py-4">
                                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-medium border border-gray-200">
                                    <PhCalendarBlank :size="14" />
                                    {{ formatDate(planning.start_date) }} - {{ formatDate(planning.end_date) }}
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2 opacity-100 sm:opacity-60 sm:group-hover:opacity-100 transition-opacity">
                                    
                                    <Link :href="route('plannings.show', planning.id)" 
                                          title="Voir / Modifier"
                                          class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <PhEye :size="20" weight="bold" />
                                    </Link>

                                    <div class="w-px h-4 bg-gray-300 mx-1"></div>

                                    <a :href="route('plannings.pdf', planning.id)" 
                                       target="_blank"
                                       title="Télécharger PDF"
                                       class="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                        <PhFilePdf :size="20" weight="bold" />
                                    </a>

                                    <a :href="route('plannings.export', planning.id)" 
                                       target="_blank"
                                       title="Télécharger Excel"
                                       class="p-2 text-gray-500 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors">
                                        <PhFileXls :size="20" weight="bold" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="plannings.data.length > 0" class="border-t border-gray-200 px-6 py-4 flex items-center justify-between bg-gray-50">
                <div class="text-xs text-gray-500">
                    Affichage de <strong>{{ plannings.from }}</strong> à <strong>{{ plannings.to }}</strong> sur <strong>{{ plannings.total }}</strong> résultats
                </div>
                <div class="flex gap-1">
                    <Link v-for="(link, k) in plannings.links" 
                          :key="k"
                          :href="link.url ?? '#'"
                          v-html="link.label"
                          class="px-3 py-1 text-xs rounded border transition-colors"
                          :class="[
                              link.active ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-100',
                              !link.url ? 'opacity-50 pointer-events-none' : ''
                          ]"
                    />
                </div>
            </div>

        </div>
    </div>
</template>