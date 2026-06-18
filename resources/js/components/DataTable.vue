<script setup lang="ts" generic="TData, TValue">
import type { ColumnDef } from '@tanstack/vue-table'
import {
  FlexRender,
  getCoreRowModel,
  useVueTable,
} from '@tanstack/vue-table'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table'

const props = defineProps<{
  columns: ColumnDef<TData, TValue>[]
  data: TData[]
}>()

const table = useVueTable({
  get data() {
 return props.data 
},
  get columns() {
 return props.columns 
},
  getCoreRowModel: getCoreRowModel(),
})
</script>

<template>
  <div class="w-full overflow-auto">
    <Table>
      <TableHeader class="bg-slate-50/80 border-y border-slate-200">
        <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id" class="hover:bg-transparent border-none">
          <TableHead 
            v-for="header in headerGroup.headers" 
            :key="header.id" 
            class="h-11 px-6 text-[11px] font-bold text-slate-500 uppercase tracking-wider align-middle"
          >
            <FlexRender
              v-if="!header.isPlaceholder"
              :render="header.column.columnDef.header"
              :props="header.getContext()"
            />
          </TableHead>
        </TableRow>
      </TableHeader>
      
      <TableBody class="bg-white">
        <template v-if="table.getRowModel().rows?.length">
          <TableRow
            v-for="row in table.getRowModel().rows"
            :key="row.id"
            :data-state="row.getIsSelected() ? 'selected' : undefined"
            class="border-b border-slate-100 hover:bg-slate-50/60 transition-colors duration-150"
          >
            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id" class="px-6 py-4 align-middle">
              <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
            </TableCell>
          </TableRow>
        </template>
        <template v-else>
          <TableRow class="hover:bg-transparent">
            <TableCell :colspan="columns.length" class="h-40 text-center text-slate-500">
              <div class="flex flex-col items-center justify-center gap-3">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center">
                  <i class="pi pi-inbox text-xl text-slate-400"></i>
                </div>
                <span class="text-sm font-medium">Data pengguna tidak ditemukan.</span>
              </div>
            </TableCell>
          </TableRow>
        </template>
      </TableBody>
    </Table>
  </div>
</template>