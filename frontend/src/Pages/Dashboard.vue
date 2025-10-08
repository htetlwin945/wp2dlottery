<template>
  <div>
    <h1 class="text-2xl font-bold">Lottery Dashboard</h1>
    <p>{{ message }}</p>
  </div>
</template>

<script setup>
defineProps({
  message: String,
});
</script>