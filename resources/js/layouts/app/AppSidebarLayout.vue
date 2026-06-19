<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';
import { useTheme } from '@/composables/useTheme';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const { theme } = useTheme();
</script>

<template>
    <AppShell variant="sidebar" :class="[theme === 'classic' ? 'font-serif' : '']">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden relative min-h-screen">
            <!-- BACKGROUND DECORATIONS -->
            
            <!-- 1. Elegan Background Grid + Minimal Chemistry Vectors -->
            <div v-if="theme === 'elegan'" class="absolute inset-0 bg-[#f8fafc] dark:bg-slate-950 pointer-events-none -z-10 transition-colors duration-500 overflow-hidden">
                <div class="absolute inset-0 bg-[linear-gradient(to_right,#e2e8f0_1px,transparent_1px),linear-gradient(to_bottom,#e2e8f0_1px,transparent_1px)] dark:bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:4rem_4rem] [mask-image:radial-gradient(ellipse_60%_50%_at_50%_0%,#000_70%,transparent_100%)] opacity-30"></div>
                
                <!-- Subtle vector chemistry illustrations in corners -->
                <div class="absolute top-12 left-[15%]">
                    <svg viewBox="0 0 100 100" class="w-24 h-24 stroke-current text-indigo-500/10 dark:text-indigo-400/5" fill="none" stroke-width="1.5">
                        <polygon points="50,5 90,28 90,72 50,95 10,72 10,28" />
                        <polygon points="50,15 82,33 82,67 50,85 18,67 18,33" stroke-dasharray="3 3" />
                        <circle cx="50" cy="50" r="4" fill="currentColor" class="opacity-30" />
                    </svg>
                </div>
                <div class="absolute bottom-24 right-[10%]">
                    <svg viewBox="0 0 100 100" class="w-20 h-20 stroke-current text-indigo-500/10 dark:text-indigo-400/5" fill="none" stroke-width="1.5">
                        <path d="M35,10 L65,10 M43,10 L43,35 L20,80 A6,6 0 0,0 25,88 L75,88 A6,6 0 0,0 80,80 L57,35 L57,10" />
                        <line x1="28" y1="72" x2="72" y2="72" stroke-dasharray="2 2" />
                        <line x1="31" y1="62" x2="69" y2="62" stroke-dasharray="2 2" />
                    </svg>
                </div>
                <div class="absolute top-1/3 right-[15%]">
                    <svg viewBox="0 0 100 100" class="w-16 h-16 stroke-current text-indigo-500/10 dark:text-indigo-400/5" fill="none" stroke-width="1.5">
                        <ellipse cx="50" cy="50" rx="40" ry="14" transform="rotate(30 50 50)" />
                        <ellipse cx="50" cy="50" rx="40" ry="14" transform="rotate(90 50 50)" />
                        <ellipse cx="50" cy="50" rx="40" ry="14" transform="rotate(150 50 50)" />
                        <circle cx="50" cy="50" r="5" fill="currentColor" class="opacity-50" />
                    </svg>
                </div>
            </div>

            <!-- 2. GenZ Neon Glow Background + Rising Laboratory Bubbles -->
            <div v-if="theme === 'genz'" class="absolute inset-0 bg-gradient-to-br from-[#fdf4ff] via-[#fae8ff] to-[#f0fdfa] dark:from-slate-950 dark:via-[#1e1b4b]/20 dark:to-slate-950 pointer-events-none -z-10 transition-colors duration-500 overflow-hidden">
                <!-- Floating Glow Bubbles -->
                <div class="absolute top-[-10%] left-[-10%] w-[350px] h-[350px] rounded-full bg-pink-400/10 dark:bg-pink-500/5 blur-[80px] animate-pulse"></div>
                <div class="absolute bottom-[-10%] right-[-10%] w-[400px] h-[400px] rounded-full bg-cyan-400/10 dark:bg-cyan-500/5 blur-[100px] animate-pulse" style="animation-duration: 6s;"></div>
                
                <!-- Floating Neon Chemistry Emotes -->
                <div class="absolute bottom-[10%] left-[5%] text-[50px] opacity-15 filter drop-shadow-[0_0_10px_rgba(217,70,239,0.5)] animate-float-slow">🧪</div>
                <div class="absolute top-[15%] right-[8%] text-[45px] opacity-15 filter drop-shadow-[0_0_10px_rgba(6,182,212,0.5)] animate-float-slow" style="animation-delay: 2s;">⚛️</div>
                <div class="absolute bottom-[35%] right-[5%] text-[40px] opacity-15 filter drop-shadow-[0_0_10px_rgba(234,179,8,0.5)] animate-float-slow" style="animation-delay: 1s;">⚗️</div>
                
                <span class="absolute top-[30%] left-[10%] text-xl opacity-20 select-none animate-bounce">✨</span>
                <span class="absolute bottom-[20%] left-[20%] text-xl opacity-15 select-none animate-bounce" style="animation-delay: 1.5s;">🚀</span>
                
                <!-- Animated Chemistry Bubbles -->
                <div class="chem-bubble bubble-1"></div>
                <div class="chem-bubble bubble-2"></div>
                <div class="chem-bubble bubble-3"></div>
                <div class="chem-bubble bubble-4"></div>
                <div class="chem-bubble bubble-5"></div>
            </div>

            <!-- 3. Classic Retro Cream Background + Vintage Chemistry Book Watermarks -->
            <div v-if="theme === 'classic'" class="absolute inset-0 bg-[#fffdf5] dark:bg-[#0c0d0a] pointer-events-none -z-10 transition-colors duration-500">
                <div class="absolute inset-2 border-2 border-double border-[#e6dfc9] dark:border-[#383526] rounded-2xl"></div>
                
                <!-- Vintage Chemical Formulas and Diagrams -->
                <div class="absolute top-16 left-12 text-[#b45309]/5 dark:text-[#b45309]/10 text-[13px] font-serif select-none italic max-w-xs leading-relaxed">
                    H₂O (Water molecule)<br>
                    2H₂ + O₂ ➔ 2H₂O
                </div>
                <div class="absolute bottom-20 right-16 text-[#b45309]/5 dark:text-[#b45309]/10 text-[12px] font-serif select-none italic text-right leading-relaxed">
                    C₆H₁₂O₆ + 6O₂ ➔ 6CO₂ + 6H₂O<br>
                    pH = -log[H⁺]
                </div>
                <div class="absolute top-1/4 right-16 text-[#b45309]/4 dark:text-[#b45309]/8 text-base font-serif select-none tracking-widest uppercase">
                    ⌬ CHIMIA RETRO
                </div>
                <div class="absolute bottom-[25%] left-16 text-[#b45309]/5 dark:text-[#b45309]/10 text-[12px] font-serif select-none leading-relaxed">
                    [ Benzene Ring Structure ]<br>
                    ⌬ C₆H₆
                </div>
            </div>

            <!-- MAIN CONTENT WRAPPER -->
            <div :class="['relative z-10 flex flex-col min-h-screen', theme === 'classic' ? 'p-4 md:p-6' : '']">
                <AppSidebarHeader :breadcrumbs="breadcrumbs" />
                <div class="flex-1">
                    <slot />
                </div>
            </div>
        </AppContent>
        <Toaster />
    </AppShell>
</template>

<style scoped>
@keyframes floatUp {
  0% {
    transform: translateY(110vh) scale(0.6);
    opacity: 0;
  }
  15% {
    opacity: 0.35;
  }
  85% {
    opacity: 0.35;
  }
  100% {
    transform: translateY(-10vh) scale(1.1);
    opacity: 0;
  }
}

@keyframes floatSlow {
  0%, 100% {
    transform: translateY(0px) rotate(0deg);
  }
  50% {
    transform: translateY(-15px) rotate(8deg);
  }
}

.animate-float-slow {
  animation: floatSlow 6s infinite ease-in-out;
}

.chem-bubble {
  position: absolute;
  bottom: -40px;
  border-radius: 50%;
  opacity: 0;
  animation: floatUp 12s infinite linear;
}

.bubble-1 {
  left: 15%;
  width: 14px;
  height: 14px;
  background: rgba(217, 70, 239, 0.25);
  box-shadow: 0 0 8px rgba(217, 70, 239, 0.4);
  animation-duration: 9s;
  animation-delay: 0s;
}

.bubble-2 {
  left: 45%;
  width: 22px;
  height: 22px;
  background: rgba(6, 182, 212, 0.2);
  box-shadow: 0 0 10px rgba(6, 182, 212, 0.3);
  animation-duration: 13s;
  animation-delay: 2s;
}

.bubble-3 {
  left: 70%;
  width: 12px;
  height: 12px;
  background: rgba(217, 70, 239, 0.3);
  box-shadow: 0 0 6px rgba(217, 70, 239, 0.4);
  animation-duration: 8s;
  animation-delay: 4s;
}

.bubble-4 {
  left: 32%;
  width: 18px;
  height: 18px;
  background: rgba(6, 182, 212, 0.25);
  box-shadow: 0 0 8px rgba(6, 182, 212, 0.4);
  animation-duration: 11s;
  animation-delay: 6s;
}

.bubble-5 {
  left: 88%;
  width: 16px;
  height: 16px;
  background: rgba(234, 179, 8, 0.2);
  box-shadow: 0 0 8px rgba(234, 179, 8, 0.3);
  animation-duration: 15s;
  animation-delay: 1s;
}
</style>
