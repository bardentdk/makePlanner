<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { 
    PhCalendarCheck, 
    PhGraduationCap, 
    PhSignOut, 
    PhSquaresFour
} from '@phosphor-icons/vue';

// Permet de vérifier la route active pour colorer le menu
const isUrl = (...urls) => {
    let currentUrl = usePage().url.substr(1);
    if (urls[0] === '') {
        return currentUrl === '';
    }
    return urls.filter((url) => currentUrl.startsWith(url)).length;
};
</script>

<template>
    <div class="flex h-screen bg-gray-50 font-sans text-gray-900">
        
        <aside class="w-64 bg-white border-r border-gray-200 flex flex-col justify-between fixed h-full z-10">
            
            <div>
                <div class="h-16 flex items-center px-6 border-b border-gray-100">
                    <div class="text-xl font-bold text-indigo-600 tracking-tight flex items-center gap-2">
                        <PhCalendarCheck weight="fill" :size="28" />
                        <!-- <span>Australe<span class="text-gray-400">Formation</span></span> -->
                    </div>
                </div>

                <nav class="p-4 space-y-1">
                    
                    <Link 
                        :href="route('plannings.index')" 
                        class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                        :class="route().current('plannings.*') 
                            ? 'bg-indigo-50 text-indigo-600' 
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <PhSquaresFour :size="24" :weight="route().current('plannings.*') ? 'fill' : 'regular'" />
                        <span>Mes Plannings</span>
                    </Link>

                    <Link 
                        :href="route('trainings.index')" 
                        class="flex items-center gap-3 px-4 py-3 rounded-lg transition-colors font-medium"
                        :class="route().current('trainings.*') 
                            ? 'bg-orange-50 text-orange-600' 
                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                    >
                        <PhGraduationCap :size="24" :weight="route().current('trainings.*') ? 'fill' : 'regular'" />
                        <span>Catalogue Formations</span>
                    </Link>

                </nav>
            </div>

            <div class="p-4 border-t border-gray-100">
                <div v-if="$page.props.auth.user" class="flex items-center gap-3 px-4 py-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xs">
                        {{ $page.props.auth.user.name?.charAt(0) }}
                    </div>
                    <div class="text-sm">
                        <p class="font-medium text-gray-700">{{ $page.props.auth.user.name }}</p>
                        <p class="text-xs text-gray-400">Connecté</p>
                    </div>
                </div>
                <div v-else class="px-4 py-3 mb-2 text-sm text-gray-400">
                    Chargement...
                </div>

                <!-- <Link 
                    :href="route('logout')" 
                    method="post" 
                    as="button" 
                    class="w-full flex items-center gap-2 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                >
                    <PhSignOut :size="18" />
                    Se déconnecter
                </Link> -->
            </div>
        </aside>

        <main class="flex-1 ml-64 overflow-y-auto h-full p-8">
            <slot />
        </main>

    </div>
</template>