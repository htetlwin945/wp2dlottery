<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <h1 class="text-3xl font-bold text-gray-900">Lottery SPA</h1>
          <nav class="space-x-4">
            <Link href="/wp-admin/admin.php?page=custom-lottery-spa" :class="{ 'font-bold': $page.component === 'Dashboard' }">Dashboard</Link>
            <Link href="/wp-admin/admin.php?page=custom-lottery-spa-customers" :class="{ 'font-bold': $page.component.startsWith('Customers') }">Customers</Link>
            <Link href="/wp-admin/admin.php?page=custom-lottery-spa-entry" :class="{ 'font-bold': $page.component.startsWith('LotteryEntry') }">Lottery Entry</Link>
            <!-- Add other links here as features are built -->
          </nav>
        </div>
      </div>
    </header>
    <main>
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <slot />
      </div>
    </main>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
</script>