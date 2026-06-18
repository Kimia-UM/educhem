<script setup lang="ts">
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'

const props = defineProps<{
    modelValue: string
    placeholder?: string
}>()

const emit = defineEmits(['update:modelValue'])

// Konfigurasi Toolbar berurutan: Bold, Italic, Underline, Strike, Paragraph Format (Normal/Header),
// Ordered List, Bullet List, Subscript, Superscript, Text Align, Image, dan Clear Formatting (ikon Tx) di ujung kanan.
const toolbarOptions = [
    ['bold', 'italic', 'underline', 'strike'],
    [{ 'header': [1, 2, 3, false] }], // Menampilkan label "Normal" saat false
    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
    [{ 'script': 'sub'}, { 'script': 'super' }], 
    [{ 'align': [] }],
    ['image', 'link', 'clean']
]
</script>

<template>
    <div class="quill-custom-wrapper">
        <QuillEditor
            :content="modelValue"
            contentType="html"
            @update:content="$emit('update:modelValue', $event)"
            :placeholder="placeholder || 'Ketik materi atau pertanyaan di sini...'"
            theme="snow"
            :toolbar="toolbarOptions"
        />
    </div>
</template>

<style>
/* 
 * CENTRALIZED RICH TEXT EDITOR DESIGN SYSTEM
 * Menyelaraskan penuh Quill Editor dengan standar desain modern Tailwind UI (SaaS Premium).
 */

/* 1. Wrapper Card & Border Fungsionalitas Fokus */
.quill-custom-wrapper {
    border: 1px solid #e2e8f0; /* border-slate-200 */
    border-radius: 0.75rem; /* rounded-xl */
    background-color: #ffffff;
    overflow: visible; /* Diubah agar popover/tooltip link tidak terpotong */
    position: relative;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); /* shadow-sm */
}

/* Mengubah warna border menjadi ungu/indigo saat area editor fokus */
.quill-custom-wrapper:focus-within {
    border-color: #6366f1; /* indigo-500 */
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15); /* Ring fokus indigo */
}

/* 2. Desain Kustom Toolbar */
.quill-custom-wrapper .ql-toolbar.ql-snow {
    border: none;
    border-bottom: 1px solid #e2e8f0; /* slate-200 */
    background-color: #ffffff;
    padding: 10px 14px;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 4px;
    border-top-left-radius: 0.75rem;
    border-top-right-radius: 0.75rem;
}

/* Pembatas grup tombol */
.quill-custom-wrapper .ql-formats {
    margin-right: 12px !important;
    display: inline-flex;
    align-items: center;
    gap: 2px;
}

/* 3. Desain Kustom Tombol Toolbar & Status Aktif/Hover */
.quill-custom-wrapper .ql-snow.ql-toolbar button {
    width: 28px;
    height: 28px;
    padding: 4px;
    border-radius: 0.375rem; /* rounded-md */
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    outline: none;
}

/* Hover tombol */
.quill-custom-wrapper .ql-snow.ql-toolbar button:hover {
    background-color: #f1f5f9; /* slate-100 */
}

/* Ikon Default (Abu-abu Gelap / Slate-600) */
.quill-custom-wrapper .ql-snow.ql-toolbar button .ql-stroke {
    stroke: #475569 !important;
    stroke-width: 2px;
    transition: stroke 0.15s ease;
}
.quill-custom-wrapper .ql-snow.ql-toolbar button .ql-fill {
    fill: #475569 !important;
    transition: fill 0.15s ease;
}

/* Hover warna ikon (Highlight Indigo-600) */
.quill-custom-wrapper .ql-snow.ql-toolbar button:hover .ql-stroke {
    stroke: #4f46e5 !important;
}
.quill-custom-wrapper .ql-snow.ql-toolbar button:hover .ql-fill {
    fill: #4f46e5 !important;
}

/* Highlight Tombol Aktif (Biru/Ungu Indigo) */
.quill-custom-wrapper .ql-snow.ql-toolbar button.ql-active {
    background-color: #e0e7ff !important; /* indigo-100 */
}
.quill-custom-wrapper .ql-snow.ql-toolbar button.ql-active .ql-stroke {
    stroke: #4f46e5 !important; /* indigo-600 */
}
.quill-custom-wrapper .ql-snow.ql-toolbar button.ql-active .ql-fill {
    fill: #4f46e5 !important;
}

/* 4. Dropdown Format Paragraf "Normal" */
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header {
    width: 115px;
    height: 28px;
    margin-right: 4px;
}

/* Label Select Dropdown */
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-label {
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    padding: 0 24px 0 10px;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    background-color: #ffffff;
    display: flex;
    align-items: center;
    position: relative;
    height: 100%;
    transition: all 0.15s ease;
    border-width: 1px !important;
}

/* Hover label */
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-label:hover {
    border-color: #cbd5e1;
    color: #1e293b;
    background-color: #f8fafc;
}

/* Dropdown Aktif */
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header.ql-active .ql-picker-label {
    border-color: #6366f1;
    color: #4f46e5;
    background-color: #e0e7ff;
}

/* Ikon Chevron di Samping Dropdown */
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-label svg {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    width: 10px;
    height: 10px;
}
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-label svg .ql-stroke {
    stroke: #475569 !important;
}
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header.ql-active .ql-picker-label svg .ql-stroke {
    stroke: #4f46e5 !important;
}

/* Panel Pilihan Dropdown */
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-options {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    padding: 4px;
    background-color: #ffffff;
    z-index: 50;
    margin-top: 4px;
}
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-options .ql-picker-item {
    padding: 6px 8px;
    border-radius: 0.25rem;
    font-size: 12px;
    color: #475569;
    transition: all 0.15s ease;
}
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-options .ql-picker-item:hover,
.quill-custom-wrapper .ql-snow.ql-toolbar .ql-picker.ql-header .ql-picker-options .ql-picker-item.ql-selected {
    background-color: #f1f5f9;
    color: #4f46e5;
}

/* 5. Kontainer Area Editor Teks */
.quill-custom-wrapper .ql-container.ql-snow {
    border: none;
    background-color: #ffffff;
    font-family: inherit;
    font-size: 14px;
    color: #1e293b; /* slate-800 */
    border-bottom-left-radius: 0.75rem;
    border-bottom-right-radius: 0.75rem;
}

.quill-custom-wrapper .ql-editor {
    min-height: 160px;
    padding: 18px;
    line-height: 1.6;
}

/* Mengubah warna placeholder editor */
.quill-custom-wrapper .ql-editor.ql-blank::before {
    color: #94a3b8; /* slate-400 */
    font-style: normal;
    left: 18px;
}

/* 6. Formatting Konten Editor & Image Responsive */
.quill-custom-wrapper .ql-editor p {
    margin-bottom: 0.5rem;
}

/* Penyisipan Gambar Inline Responsive */
.quill-custom-wrapper .ql-editor img {
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem; /* rounded corner tipis */
    display: inline-block;
    margin: 8px 0;
    border: 1px solid #f1f5f9;
}

/* Layout List & Nested Indentation di Editor */
.quill-custom-wrapper .ql-editor ol, 
.quill-custom-wrapper .ql-editor ul {
    padding-left: 1.5rem;
    margin-bottom: 0.5rem;
}

/* 7. Quill Tooltip (Popup Link Insertion) */
.quill-custom-wrapper .ql-snow .ql-tooltip {
    background-color: #ffffff !important;
    border: 1px solid #e2e8f0 !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    padding: 8px 12px !important;
    z-index: 50 !important;
    color: #475569 !important;
}

.quill-custom-wrapper .ql-snow .ql-tooltip input[type=text] {
    border: 1px solid #cbd5e1 !important;
    border-radius: 0.375rem !important;
    padding: 4px 8px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    outline: none !important;
    transition: border-color 0.15s ease !important;
}

.quill-custom-wrapper .ql-snow .ql-tooltip input[type=text]:focus {
    border-color: #6366f1 !important;
}

.quill-custom-wrapper .ql-snow .ql-tooltip a.ql-action {
    color: #4f46e5 !important;
    font-weight: 600 !important;
    font-size: 13px !important;
    margin-left: 8px !important;
    text-decoration: none !important;
}

.quill-custom-wrapper .ql-snow .ql-tooltip a.ql-action:hover {
    color: #3730a3 !important;
}

.quill-custom-wrapper .ql-snow .ql-tooltip a.ql-remove {
    color: #ef4444 !important;
    font-size: 12px !important;
    margin-left: 8px !important;
    text-decoration: none !important;
}

.quill-custom-wrapper .ql-snow .ql-tooltip a.ql-remove:hover {
    color: #b91c1c !important;
}
</style>