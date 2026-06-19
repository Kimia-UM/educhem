import { ref, onMounted } from 'vue';

export type Theme = 'elegan' | 'genz' | 'classic';

const theme = ref<Theme>('elegan');

export function useTheme() {
    onMounted(() => {
        const savedTheme = localStorage.getItem('app-theme') as Theme | null;
        if (savedTheme) {
            theme.value = savedTheme;
            document.documentElement.setAttribute('data-theme', savedTheme);
        } else {
            document.documentElement.setAttribute('data-theme', 'elegan');
        }
    });

    const updateTheme = (newTheme: Theme) => {
        theme.value = newTheme;
        localStorage.setItem('app-theme', newTheme);
        document.documentElement.setAttribute('data-theme', newTheme);
    };

    return {
        theme,
        updateTheme,
    };
}
