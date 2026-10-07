<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import NavUser from '@/components/NavUser.vue';
import {
    Collapsible,
    CollapsibleTrigger,
    CollapsibleContent,
} from '@/components/ui/collapsible';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuItem,
    SidebarMenuButton,
    SidebarMenuSub,
    SidebarMenuSubItem,
    SidebarMenuSubButton,
} from '@/components/ui/sidebar';

const page = usePage();
const userRole = computed(
    () => page.props.auth.user?.roles?.[0]?.name || 'SISWA',
);

const isActiveRoute = (routePattern: string) => {
    if (typeof window === 'undefined') {
        return false;
    }

    if (routePattern === 'guru.dashboard') {
        return page.url.startsWith('/guru/dashboard');
    }

    if (routePattern === 'siswa.dashboard') {
        return page.url.startsWith('/siswa/dashboard');
    }

    if (routePattern === 'admin.dashboard') {
        return page.url.startsWith('/admin/dashboard');
    }

    if (routePattern === 'admin.users.index') {
        return page.url.startsWith('/admin/users');
    }

    if (routePattern === 'admin.password-resets.index') {
        return page.url.startsWith('/admin/password-resets');
    }

    return (
        route().current(routePattern) ||
        (routePattern.endsWith('.index') &&
            route().current(routePattern.replace('.index', '.*')))
    );
};

const isClassActive = (classroomId: number) => {
    if (typeof window === 'undefined') {
        return false;
    }

    if (userRole.value === 'GURU') {
        return (
            (route().current('guru.classes.show') &&
                route().params.class === String(classroomId)) ||
            ((route().current('guru.classes.topics.*') ||
                route().current('guru.phases.*') ||
                route().current('guru.classes.ai-chat-logs.*')) &&
                route().params.classroom === String(classroomId))
        );
    }

    if (userRole.value === 'SISWA') {
        return (
            route().current('siswa.classes.show', { classroom: classroomId }) ||
            (route().current('siswa.worksheet.*') &&
                route().params.classroom === String(classroomId))
        );
    }

    return false;
};

const menuItems = computed(() => {
    if (userRole.value === 'ADMIN') {
        return [
            {
                label: 'Dashboard',
                icon: 'pi pi-home',
                route: 'admin.dashboard',
            },
            {
                label: 'Manajemen User',
                icon: 'pi pi-users',
                route: 'admin.users.index',
            },
            {
                label: 'Reset Password',
                icon: 'pi pi-key',
                route: 'admin.password-resets.index',
            },
        ];
    }

    if (userRole.value === 'GURU') {
        return [
            { label: 'Dashboard', icon: 'pi pi-home', route: 'guru.dashboard' },
            {
                label: 'Manajemen Kelas',
                icon: 'pi pi-book',
                route: 'guru.classes.index',
            },
        ];
    }

    if (userRole.value === 'SISWA') {
        return [
            {
                label: 'Overview',
                icon: 'pi pi-th-large',
                route: 'siswa.dashboard',
            },
            {
                label: 'Kelas Saya',
                icon: 'pi pi-book',
                route: 'siswa.classes.index',
            },
        ];
    }

    return [];
});
</script>

<template>
    <Sidebar variant="inset" collapsible="icon" class="border-none">
        <SidebarHeader
            class="relative flex items-center justify-center overflow-hidden border-b border-white/5 bg-gradient-to-b from-[#112a4a] to-transparent p-5 transition-all duration-300 group-data-[collapsible=icon]:p-3"
        >
            <div
                class="absolute -top-8 -right-8 h-32 w-32 rounded-full bg-blue-500/10 blur-3xl group-data-[collapsible=icon]:hidden"
            ></div>

            <img
                src="/assets/images/Logo1.png"
                alt="Educhem Logo"
                class="relative h-10 w-auto max-w-full object-contain transition-all duration-300 group-data-[collapsible=icon]:hidden hover:scale-105"
            />
            <img
                src="/assets/images/Logo_only.png"
                alt="Educhem Icon"
                class="hidden h-7 w-auto object-contain transition-all duration-300 group-data-[collapsible=icon]:block hover:scale-115"
            />
        </SidebarHeader>

        <SidebarContent class="overflow-x-hidden px-3 py-6">
            <SidebarMenu>
                <template v-for="(item, index) in menuItems" :key="item.label">
                    <div
                        v-if="index === 0"
                        class="mb-3 px-3 text-[10.5px] font-bold tracking-[0.2em] text-slate-500/80 uppercase group-data-[collapsible=icon]:hidden"
                    >
                        Main Menu
                    </div>

                    <div
                        v-if="
                            item.label === 'Manajemen Kelas' ||
                            item.label === 'Kelas Saya'
                        "
                        class="mt-8 mb-3 px-3 text-[10.5px] font-bold tracking-[0.2em] text-slate-500/80 uppercase group-data-[collapsible=icon]:hidden"
                    >
                        Learning Content
                    </div>

                    <SidebarMenuItem
                        v-if="
                            item.label !== 'Kelas Saya' &&
                            item.label !== 'Manajemen Kelas'
                        "
                        class="group mb-1.5"
                    >
                        <template v-if="item.route">
                            <SidebarMenuButton
                                as-child
                                :is-active="
                                    item.route
                                        ? isActiveRoute(item.route)
                                        : false
                                "
                            >
                                <Link
                                    :href="route(item.route)"
                                    :aria-current="
                                        isActiveRoute(item.route)
                                            ? 'page'
                                            : undefined
                                    "
                                    class="flex h-11 w-full items-center rounded-lg px-3 transition-all duration-300 ease-in-out hover:bg-white/5"
                                >
                                    <i
                                        :class="item.icon"
                                        class="mr-3 text-[18px] opacity-80 transition-transform duration-300 group-hover:scale-110 group-hover:text-blue-400 group-data-[collapsible=icon]:mr-0"
                                    ></i>
                                    <span
                                        class="text-[14px] font-medium tracking-wide group-data-[collapsible=icon]:hidden"
                                        >{{ item.label }}</span
                                    >

                                    <span
                                        v-if="
                                            item.label === 'Reset Password' &&
                                            $page.props
                                                .pendingPasswordResetsCount > 0
                                        "
                                        class="ml-auto flex h-5 min-w-[20px] items-center justify-center rounded-full bg-gradient-to-r from-rose-500 to-red-600 px-1.5 text-[10px] font-bold text-white shadow-sm ring-1 ring-white/20 group-data-[collapsible=icon]:hidden"
                                    >
                                        {{
                                            $page.props
                                                .pendingPasswordResetsCount
                                        }}
                                    </span>
                                </Link>
                            </SidebarMenuButton>
                        </template>

                        <template v-else>
                            <SidebarMenuButton
                                disabled
                                class="flex h-11 w-full items-center rounded-lg px-3 opacity-50"
                            >
                                <i
                                    :class="item.icon"
                                    class="mr-3 text-[18px] text-slate-400 group-data-[collapsible=icon]:mr-0"
                                ></i>
                                <span
                                    class="text-[14px] font-medium text-slate-400 group-data-[collapsible=icon]:hidden"
                                    >{{ item.label }}</span
                                >
                                <span
                                    class="ml-auto rounded-full bg-white/10 px-2.5 py-0.5 text-[9px] font-bold tracking-widest text-slate-300 uppercase shadow-inner group-data-[collapsible=icon]:hidden"
                                    >Soon</span
                                >
                            </SidebarMenuButton>
                        </template>
                    </SidebarMenuItem>

                    <Collapsible
                        v-else-if="
                            item.label === 'Manajemen Kelas' &&
                            userRole === 'GURU'
                        "
                        as-child
                        default-open
                        class="group/collapsible mb-1.5"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    as-child
                                    :is-active="
                                        !page.url.startsWith('/guru/dashboard')
                                    "
                                >
                                    <button
                                        type="button"
                                        class="flex h-11 w-full items-center rounded-lg px-3 text-left hover:bg-white/5"
                                    >
                                        <i
                                            :class="item.icon"
                                            class="mr-3 text-[18px] opacity-80 transition-transform duration-300 group-hover/collapsible:text-blue-400 group-data-[collapsible=icon]:mr-0"
                                        ></i>
                                        <span
                                            class="text-[14px] font-medium tracking-wide group-data-[collapsible=icon]:hidden"
                                            >{{ item.label }}</span
                                        >
                                        <i
                                            class="pi pi-chevron-down ml-auto text-xs opacity-50 transition-transform duration-300 group-data-[collapsible=icon]:hidden group-data-[state=open]/collapsible:rotate-180"
                                        ></i>
                                    </button>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>

                            <CollapsibleContent
                                class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
                            >
                                <SidebarMenuSub class="sidebar-class-list">
                                    <SidebarMenuSubItem
                                        class="sidebar-class-node"
                                    >
                                        <div
                                            class="sidebar-tree-connector"
                                        ></div>
                                        <SidebarMenuSubButton
                                            as-child
                                            :is-active="
                                                route().current(
                                                    'guru.classes.index',
                                                )
                                            "
                                            class="sidebar-class-button sidebar-index-button"
                                        >
                                            <Link
                                                :href="
                                                    route('guru.classes.index')
                                                "
                                                :aria-current="
                                                    route().current(
                                                        'guru.classes.index',
                                                    )
                                                        ? 'page'
                                                        : undefined
                                                "
                                            >
                                                <i
                                                    class="pi pi-th-large sidebar-tree-icon"
                                                ></i>
                                                <span class="sidebar-tree-label"
                                                    >Semua kelas</span
                                                >
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>

                                    <Collapsible
                                        as-child
                                        v-for="classroom in $page.props
                                            .sidebarClasses"
                                        :key="classroom.id"
                                        class="group/class"
                                        :default-open="
                                            isClassActive(classroom.id)
                                        "
                                    >
                                        <SidebarMenuSubItem
                                            class="sidebar-class-node"
                                        >
                                            <div
                                                class="sidebar-tree-connector"
                                            ></div>

                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isClassActive(
                                                            classroom.id,
                                                        )
                                                    "
                                                    class="sidebar-class-button"
                                                >
                                                    <button type="button">
                                                        <span
                                                            class="sidebar-class-label"
                                                            >{{
                                                                classroom.class_name
                                                            }}</span
                                                        >
                                                        <i
                                                            class="pi pi-chevron-down sidebar-tree-chevron group-data-[state=open]/class:rotate-180"
                                                        ></i>
                                                    </button>
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>

                                            <CollapsibleContent
                                                class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
                                            >
                                                <SidebarMenuSub
                                                    class="sidebar-branch-list"
                                                >
                                                    <template
                                                        v-for="(
                                                            subItem, subIdx
                                                        ) in [
                                                            {
                                                                label: 'Topik Pembelajaran',
                                                                icon: 'pi-book',
                                                                active:
                                                                    (route().current(
                                                                        'guru.classes.show',
                                                                    ) &&
                                                                        route()
                                                                            .params
                                                                            .class ===
                                                                            String(
                                                                                classroom.id,
                                                                            ) &&
                                                                        $page
                                                                            .props
                                                                            .defaultTab !==
                                                                            'siswa' &&
                                                                        $page
                                                                            .props
                                                                            .defaultTab !==
                                                                            'rekapNilai') ||
                                                                    ((route().current(
                                                                        'guru.classes.topics.*',
                                                                    ) ||
                                                                        route().current(
                                                                            'guru.phases.*',
                                                                        )) &&
                                                                        route()
                                                                            .params
                                                                            .classroom ===
                                                                            String(
                                                                                classroom.id,
                                                                            )),
                                                                route: route(
                                                                    'guru.classes.show',
                                                                    classroom.id,
                                                                ),
                                                            },
                                                            {
                                                                label: 'Daftar Siswa',
                                                                icon: 'pi-users',
                                                                active:
                                                                    route().current(
                                                                        'guru.classes.show',
                                                                    ) &&
                                                                    route()
                                                                        .params
                                                                        .class ===
                                                                        String(
                                                                            classroom.id,
                                                                        ) &&
                                                                    $page.props
                                                                        .defaultTab ===
                                                                        'siswa',
                                                                route: route(
                                                                    'guru.classes.show',
                                                                    {
                                                                        class: classroom.id,
                                                                        tab: 'siswa',
                                                                    },
                                                                ),
                                                            },
                                                            {
                                                                label: 'Log Chatbot AI',
                                                                icon: 'pi-comments',
                                                                active: route().current(
                                                                    'guru.classes.ai-chat-logs.index',
                                                                    {
                                                                        classroom:
                                                                            classroom.id,
                                                                    },
                                                                ),
                                                                route: route(
                                                                    'guru.classes.ai-chat-logs.index',
                                                                    {
                                                                        classroom:
                                                                            classroom.id,
                                                                    },
                                                                ),
                                                            },
                                                            {
                                                                label: 'Rekap Nilai',
                                                                icon: 'pi-percentage',
                                                                active:
                                                                    route().current(
                                                                        'guru.classes.show',
                                                                    ) &&
                                                                    route()
                                                                        .params
                                                                        .class ===
                                                                        String(
                                                                            classroom.id,
                                                                        ) &&
                                                                    $page.props
                                                                        .defaultTab ===
                                                                        'rekapNilai',
                                                                route: route(
                                                                    'guru.classes.show',
                                                                    {
                                                                        class: classroom.id,
                                                                        tab: 'rekapNilai',
                                                                    },
                                                                ),
                                                            },
                                                        ]"
                                                        :key="subIdx"
                                                    >
                                                        <SidebarMenuSubItem
                                                            class="sidebar-branch-node"
                                                        >
                                                            <div
                                                                class="sidebar-tree-connector sidebar-tree-connector--muted"
                                                            ></div>
                                                            <SidebarMenuSubButton
                                                                as-child
                                                                :is-active="
                                                                    subItem.active
                                                                "
                                                                class="sidebar-branch-button"
                                                            >
                                                                <Link
                                                                    :href="
                                                                        subItem.route
                                                                    "
                                                                    :aria-current="
                                                                        subItem.active
                                                                            ? 'page'
                                                                            : undefined
                                                                    "
                                                                >
                                                                    <i
                                                                        :class="`pi ${subItem.icon} sidebar-tree-icon`"
                                                                    ></i>
                                                                    <span
                                                                        class="sidebar-tree-label"
                                                                        :title="
                                                                            subItem.label
                                                                        "
                                                                        >{{
                                                                            subItem.label
                                                                        }}</span
                                                                    >
                                                                </Link>
                                                            </SidebarMenuSubButton>
                                                        </SidebarMenuSubItem>
                                                    </template>
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuSubItem>
                                    </Collapsible>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>

                    <Collapsible
                        v-else-if="
                            item.label === 'Kelas Saya' && userRole === 'SISWA'
                        "
                        as-child
                        default-open
                        class="group/collapsible mb-1.5"
                    >
                        <SidebarMenuItem>
                            <CollapsibleTrigger as-child>
                                <SidebarMenuButton
                                    as-child
                                    :is-active="
                                        !page.url.startsWith('/siswa/dashboard')
                                    "
                                >
                                    <button
                                        type="button"
                                        class="flex h-11 w-full items-center rounded-lg px-3 text-left hover:bg-white/5"
                                    >
                                        <i
                                            :class="item.icon"
                                            class="mr-3 text-[18px] opacity-80 transition-transform duration-300 group-hover/collapsible:text-blue-400 group-data-[collapsible=icon]:mr-0"
                                        ></i>
                                        <span
                                            class="text-[14px] font-medium tracking-wide group-data-[collapsible=icon]:hidden"
                                            >{{ item.label }}</span
                                        >
                                        <i
                                            class="pi pi-chevron-down ml-auto text-xs opacity-50 transition-transform duration-300 group-data-[collapsible=icon]:hidden group-data-[state=open]/collapsible:rotate-180"
                                        ></i>
                                    </button>
                                </SidebarMenuButton>
                            </CollapsibleTrigger>

                            <CollapsibleContent
                                class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
                            >
                                <SidebarMenuSub class="sidebar-class-list">
                                    <SidebarMenuSubItem
                                        class="sidebar-class-node"
                                    >
                                        <div
                                            class="sidebar-tree-connector"
                                        ></div>
                                        <SidebarMenuSubButton
                                            as-child
                                            :is-active="
                                                route().current(
                                                    'siswa.classes.index',
                                                )
                                            "
                                            class="sidebar-class-button sidebar-index-button"
                                        >
                                            <Link
                                                :href="
                                                    route('siswa.classes.index')
                                                "
                                                :aria-current="
                                                    route().current(
                                                        'siswa.classes.index',
                                                    )
                                                        ? 'page'
                                                        : undefined
                                                "
                                            >
                                                <i
                                                    class="pi pi-th-large sidebar-tree-icon"
                                                ></i>
                                                <span class="sidebar-tree-label"
                                                    >Semua kelas</span
                                                >
                                            </Link>
                                        </SidebarMenuSubButton>
                                    </SidebarMenuSubItem>

                                    <Collapsible
                                        as-child
                                        v-for="classroom in $page.props
                                            .sidebarClasses"
                                        :key="classroom.id"
                                        class="group/class"
                                        :default-open="
                                            isClassActive(classroom.id)
                                        "
                                    >
                                        <SidebarMenuSubItem
                                            class="sidebar-class-node"
                                        >
                                            <div
                                                class="sidebar-tree-connector"
                                            ></div>

                                            <CollapsibleTrigger as-child>
                                                <SidebarMenuSubButton
                                                    as-child
                                                    :is-active="
                                                        isClassActive(
                                                            classroom.id,
                                                        )
                                                    "
                                                    class="sidebar-class-button"
                                                >
                                                    <button type="button">
                                                        <span
                                                            class="sidebar-class-label"
                                                            >{{
                                                                classroom.class_name
                                                            }}</span
                                                        >
                                                        <i
                                                            class="pi pi-chevron-down sidebar-tree-chevron group-data-[state=open]/class:rotate-180"
                                                        ></i>
                                                    </button>
                                                </SidebarMenuSubButton>
                                            </CollapsibleTrigger>

                                            <CollapsibleContent
                                                class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
                                            >
                                                <SidebarMenuSub
                                                    class="sidebar-branch-list"
                                                >
                                                    <SidebarMenuSubItem
                                                        class="sidebar-branch-node"
                                                    >
                                                        <div
                                                            class="sidebar-tree-connector sidebar-tree-connector--muted"
                                                        ></div>
                                                        <SidebarMenuSubButton
                                                            as-child
                                                            :is-active="
                                                                route().current(
                                                                    'siswa.classes.show',
                                                                    {
                                                                        classroom:
                                                                            classroom.id,
                                                                    },
                                                                )
                                                            "
                                                            class="sidebar-branch-button"
                                                        >
                                                            <Link
                                                                :href="
                                                                    route(
                                                                        'siswa.classes.show',
                                                                        {
                                                                            classroom:
                                                                                classroom.id,
                                                                        },
                                                                    )
                                                                "
                                                                :aria-current="
                                                                    route().current(
                                                                        'siswa.classes.show',
                                                                        {
                                                                            classroom:
                                                                                classroom.id,
                                                                        },
                                                                    )
                                                                        ? 'page'
                                                                        : undefined
                                                                "
                                                            >
                                                                <i
                                                                    class="pi pi-home sidebar-tree-icon"
                                                                ></i>
                                                                <span
                                                                    class="sidebar-tree-label"
                                                                    >Ringkasan
                                                                    kelas</span
                                                                >
                                                            </Link>
                                                        </SidebarMenuSubButton>
                                                    </SidebarMenuSubItem>

                                                    <Collapsible
                                                        as-child
                                                        v-for="topic in classroom.topics"
                                                        :key="topic.id"
                                                        class="group/topic"
                                                        :default-open="
                                                            route().current(
                                                                'siswa.worksheet.*',
                                                            ) &&
                                                            route().params
                                                                .topic ===
                                                                String(topic.id)
                                                        "
                                                        :disabled="
                                                            !topic.phases ||
                                                            topic.phases
                                                                .length === 0
                                                        "
                                                    >
                                                        <SidebarMenuSubItem
                                                            class="sidebar-branch-node"
                                                        >
                                                            <div
                                                                class="sidebar-tree-connector sidebar-tree-connector--muted"
                                                            ></div>

                                                            <CollapsibleTrigger
                                                                as-child
                                                            >
                                                                <SidebarMenuSubButton
                                                                    as-child
                                                                    class="sidebar-branch-button"
                                                                    :is-active="
                                                                        route().current(
                                                                            'siswa.worksheet.*',
                                                                        ) &&
                                                                        route()
                                                                            .params
                                                                            .topic ===
                                                                            String(
                                                                                topic.id,
                                                                            )
                                                                    "
                                                                    :disabled="
                                                                        !topic.phases ||
                                                                        topic
                                                                            .phases
                                                                            .length ===
                                                                            0
                                                                    "
                                                                >
                                                                    <button
                                                                        type="button"
                                                                        :disabled="
                                                                            !topic.phases ||
                                                                            topic
                                                                                .phases
                                                                                .length ===
                                                                                0
                                                                        "
                                                                    >
                                                                        <span
                                                                            class="sidebar-tree-label"
                                                                            :title="
                                                                                topic.title
                                                                            "
                                                                            >{{
                                                                                topic.title
                                                                            }}</span
                                                                        >
                                                                        <i
                                                                            v-if="
                                                                                topic.phases &&
                                                                                topic
                                                                                    .phases
                                                                                    .length >
                                                                                    0
                                                                            "
                                                                            class="pi pi-chevron-down sidebar-tree-chevron group-data-[state=open]/topic:rotate-180"
                                                                        ></i>
                                                                    </button>
                                                                </SidebarMenuSubButton>
                                                            </CollapsibleTrigger>

                                                            <CollapsibleContent
                                                                class="overflow-hidden transition-all data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down"
                                                            >
                                                                <SidebarMenuSub
                                                                    class="sidebar-leaf-list"
                                                                >
                                                                    <SidebarMenuSubItem
                                                                        v-for="phase in topic.phases"
                                                                        :key="
                                                                            phase.id
                                                                        "
                                                                        class="sidebar-leaf-node"
                                                                    >
                                                                        <div
                                                                            class="sidebar-tree-connector sidebar-tree-connector--faint"
                                                                        ></div>

                                                                        <SidebarMenuSubButton
                                                                            as-child
                                                                            class="sidebar-leaf-button"
                                                                            :is-active="
                                                                                route().current(
                                                                                    'siswa.worksheet.show',
                                                                                    {
                                                                                        classroom:
                                                                                            classroom.id,
                                                                                        topic: topic.id,
                                                                                        phase: phase.id,
                                                                                    },
                                                                                )
                                                                            "
                                                                        >
                                                                            <Link
                                                                                :href="
                                                                                    route(
                                                                                        'siswa.worksheet.show',
                                                                                        {
                                                                                            classroom:
                                                                                                classroom.id,
                                                                                            topic: topic.id,
                                                                                            phase: phase.id,
                                                                                        },
                                                                                    )
                                                                                "
                                                                                :aria-current="
                                                                                    route().current(
                                                                                        'siswa.worksheet.show',
                                                                                        {
                                                                                            classroom:
                                                                                                classroom.id,
                                                                                            topic: topic.id,
                                                                                            phase: phase.id,
                                                                                        },
                                                                                    )
                                                                                        ? 'page'
                                                                                        : undefined
                                                                                "
                                                                            >
                                                                                <i
                                                                                    class="pi pi-file-edit sidebar-tree-icon"
                                                                                ></i>
                                                                                <span
                                                                                    class="sidebar-tree-label"
                                                                                    :title="
                                                                                        phase.name
                                                                                    "
                                                                                    >{{
                                                                                        phase.name
                                                                                    }}</span
                                                                                >
                                                                            </Link>
                                                                        </SidebarMenuSubButton>
                                                                    </SidebarMenuSubItem>
                                                                </SidebarMenuSub>
                                                            </CollapsibleContent>
                                                        </SidebarMenuSubItem>
                                                    </Collapsible>
                                                </SidebarMenuSub>
                                            </CollapsibleContent>
                                        </SidebarMenuSubItem>
                                    </Collapsible>
                                </SidebarMenuSub>
                            </CollapsibleContent>
                        </SidebarMenuItem>
                    </Collapsible>
                </template>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter
            class="border-t border-white/5 bg-[#081627] p-4 group-data-[collapsible=icon]:p-2"
        >
            <div
                class="rounded-xl border border-white/5 bg-white/[0.03] p-1.5 shadow-sm transition-all group-data-[collapsible=icon]:p-1 hover:border-white/10 hover:bg-white/[0.08]"
            >
                <NavUser
                    class="bg-transparent text-white hover:bg-transparent"
                />
            </div>
        </SidebarFooter>
    </Sidebar>
</template>

<style scoped>
/* 1. Base Navy Background Override (Safety Fallback) */
:deep(.bg-sidebar) {
    background-color: #0b1e36 !important;
    --sidebar-foreground: #cbd5e1; /* Slate 300 base */
    --sidebar-border: rgba(255, 255, 255, 0.05);
}
:deep(.text-sidebar-foreground) {
    color: #cbd5e1 !important;
}

/* 2. Interactive states shared by every navigation level */
:deep([data-sidebar='menu-button']),
:deep([data-sidebar='menu-sub-button']) {
    position: relative;
    color: #94a3b8 !important;
    background: transparent !important;
    border: 1px solid transparent !important;
    box-shadow: none !important;
    transition:
        color 160ms ease,
        background-color 160ms ease,
        border-color 160ms ease,
        opacity 160ms ease !important;
}

:deep([data-sidebar='menu-button'] i),
:deep([data-sidebar='menu-sub-button'] i) {
    color: #64748b !important;
    opacity: 1 !important;
    transition: color 160ms ease !important;
}

/* Hover is visible but deliberately quieter than the current-page state. */
:deep(
    [data-sidebar='menu-button']:not([data-active='true']):not(:disabled):not(
            [aria-disabled='true']
        ):hover
),
:deep(
    [data-sidebar='menu-sub-button']:not([data-active='true']):not(
            :disabled
        ):not([aria-disabled='true']):hover
) {
    color: #e2e8f0 !important;
    background: rgba(255, 255, 255, 0.055) !important;
    border-color: rgba(255, 255, 255, 0.06) !important;
}

:deep([data-sidebar='menu-button']:not([data-active='true']):hover i),
:deep([data-sidebar='menu-sub-button']:not([data-active='true']):hover i) {
    color: #94a3b8 !important;
}

/* Current top-level section: filled surface plus an inset position marker. */
:deep([data-sidebar='menu-button'][data-active='true']) {
    color: #ffffff !important;
    background: linear-gradient(
        90deg,
        rgba(37, 99, 235, 0.22),
        rgba(37, 99, 235, 0.08)
    ) !important;
    border-color: rgba(96, 165, 250, 0.2) !important;
    border-radius: 0.5rem !important;
    font-weight: 650 !important;
}

:deep([data-sidebar='menu-button'][data-active='true']::before) {
    position: absolute;
    top: 0.5rem;
    bottom: 0.5rem;
    left: 0.1875rem;
    width: 0.1875rem;
    border-radius: 999px;
    background: #60a5fa;
    content: '';
}

:deep([data-sidebar='menu-button'][data-active='true'] i) {
    color: #60a5fa !important;
}

/* Current nested item: same color language at a quieter intensity. */
:deep([data-sidebar='menu-sub-button'][data-active='true']) {
    color: #eff6ff !important;
    background: rgba(59, 130, 246, 0.14) !important;
    border-color: rgba(96, 165, 250, 0.18) !important;
    font-weight: 600 !important;
}

:deep([data-sidebar='menu-sub-button'][data-active='true'] i) {
    color: #93c5fd !important;
}

/* Pressed state is momentary and never changes the button geometry. */
:deep(
    [data-sidebar='menu-button']:not(:disabled):not(
            [aria-disabled='true']
        ):active
),
:deep(
    [data-sidebar='menu-sub-button']:not(:disabled):not(
            [aria-disabled='true']
        ):active
) {
    background: rgba(255, 255, 255, 0.1) !important;
}

:deep([data-sidebar='menu-button'][data-active='true']:active),
:deep([data-sidebar='menu-sub-button'][data-active='true']:active) {
    background: rgba(37, 99, 235, 0.24) !important;
}

/* Keyboard focus is distinct from selected/current state. */
:deep([data-sidebar='menu-button']:focus-visible),
:deep([data-sidebar='menu-sub-button']:focus-visible) {
    outline: 2px solid #60a5fa !important;
    outline-offset: -2px !important;
    box-shadow: none !important;
}

:deep([data-sidebar='menu-button']:focus:not(:focus-visible)),
:deep([data-sidebar='menu-sub-button']:focus:not(:focus-visible)) {
    outline: none !important;
    box-shadow: none !important;
}

:deep([data-sidebar='menu-button']:disabled),
:deep([data-sidebar='menu-button'][aria-disabled='true']),
:deep([data-sidebar='menu-sub-button']:disabled),
:deep([data-sidebar='menu-sub-button'][aria-disabled='true']) {
    color: #64748b !important;
    background: transparent !important;
    border-color: transparent !important;
    opacity: 0.45 !important;
    cursor: not-allowed !important;
}

/* 3. Nested navigation: compact indentation without sacrificing label width */
:deep(.sidebar-class-list) {
    width: auto !important;
    min-width: 0 !important;
    margin: 0.5rem 0 0 1rem !important;
    padding: 0 0 0.125rem 0.625rem !important;
    transform: none !important;
    gap: 0.375rem !important;
    border-color: rgba(148, 163, 184, 0.2) !important;
}

:deep(.sidebar-class-node),
:deep(.sidebar-branch-node),
:deep(.sidebar-leaf-node) {
    min-width: 0 !important;
    padding: 0 !important;
}

:deep(.sidebar-tree-connector) {
    position: absolute;
    top: 1.125rem;
    left: -0.625rem;
    width: 0.625rem;
    height: 1px;
    background: rgba(148, 163, 184, 0.2);
    pointer-events: none;
}

:deep(.sidebar-tree-connector--muted) {
    top: 1rem;
    left: -0.55rem;
    width: 0.55rem;
    background: rgba(148, 163, 184, 0.14);
}

:deep(.sidebar-tree-connector--faint) {
    top: 0.875rem;
    left: -0.5rem;
    width: 0.5rem;
    background: rgba(148, 163, 184, 0.1);
}

:deep(.sidebar-class-button) {
    display: flex !important;
    width: 100% !important;
    min-width: 0 !important;
    min-height: 2.25rem !important;
    height: auto !important;
    padding: 0.45rem 0.625rem !important;
    align-items: center !important;
    gap: 0.5rem !important;
    overflow: hidden !important;
    transform: none !important;
    border-radius: 0.5rem !important;
}

:deep(.sidebar-index-button) {
    min-height: 2rem !important;
    height: 2rem !important;
    padding: 0 0.5rem !important;
    font-size: 0.78125rem !important;
    font-weight: 500 !important;
}

:deep(.sidebar-class-label) {
    display: -webkit-box;
    min-width: 0;
    flex: 1 1 auto;
    overflow: hidden;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    overflow-wrap: anywhere;
    font-size: 0.8125rem;
    font-weight: 650;
    line-height: 1.2rem;
    letter-spacing: 0.01em;
}

:deep(.sidebar-branch-list) {
    width: auto !important;
    min-width: 0 !important;
    margin: 0.25rem 0 0.5rem 0.45rem !important;
    padding: 0 0 0 0.55rem !important;
    transform: none !important;
    gap: 0.125rem !important;
    border-color: rgba(148, 163, 184, 0.14) !important;
}

:deep(.sidebar-leaf-list) {
    width: auto !important;
    min-width: 0 !important;
    margin: 0.125rem 0 0.375rem 0.4rem !important;
    padding: 0 0 0 0.5rem !important;
    transform: none !important;
    gap: 0.125rem !important;
    border-color: rgba(148, 163, 184, 0.1) !important;
}

:deep(.sidebar-branch-button),
:deep(.sidebar-leaf-button) {
    display: flex !important;
    width: 100% !important;
    min-width: 0 !important;
    align-items: center !important;
    gap: 0.5rem !important;
    overflow: hidden !important;
    transform: none !important;
    border-radius: 0.375rem !important;
    transition:
        color 0.2s ease,
        background-color 0.2s ease,
        opacity 0.2s ease !important;
}

:deep(.sidebar-branch-button) {
    height: 2rem !important;
    padding: 0 0.5rem !important;
    font-size: 0.78125rem !important;
    opacity: 0.8;
}

:deep(.sidebar-leaf-button) {
    height: 1.75rem !important;
    padding: 0 0.4rem !important;
    gap: 0.4rem !important;
    font-size: 0.75rem !important;
    opacity: 0.68;
}

:deep(.sidebar-branch-button:hover),
:deep(.sidebar-leaf-button:hover),
:deep(.sidebar-branch-button[data-active='true']),
:deep(.sidebar-leaf-button[data-active='true']) {
    opacity: 1;
}

:deep(.sidebar-tree-label) {
    min-width: 0;
    flex: 1 1 auto;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

:deep(.sidebar-tree-icon) {
    width: 0.875rem;
    flex: 0 0 0.875rem;
    font-size: 0.6875rem;
    text-align: center;
}

:deep(.sidebar-tree-chevron) {
    margin-left: auto;
    flex: 0 0 auto;
    font-size: 0.625rem;
    opacity: 0.5;
    transition: transform 0.3s ease;
}

/* 4. Tooltip/Collapsed specific adjustments */
:deep([data-state='collapsed'] .text-\[10\.5px\]) {
    display: none !important; /* Sembunyikan label saat ditutup */
}

/* 5. Custom collapsed size for buttons and alignment */
:deep([data-collapsible='icon'] [data-sidebar='menu-button']) {
    width: 2.5rem !important; /* 40px */
    height: 2.5rem !important; /* 40px */
    padding: 0 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin: 0 auto !important;
}
</style>
