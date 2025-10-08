<template>
  <Layout>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Customers</h1>
      <button @click="openModal()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
        Add Customer
      </button>
    </div>

    <!-- Customer List Table -->
    <div class="bg-white shadow-md rounded my-6">
      <table class="min-w-max w-full table-auto">
        <thead>
          <tr class="bg-gray-200 text-gray-600 uppercase text-sm leading-normal">
            <th class="py-3 px-6 text-left">ID</th>
            <th class="py-3 px-6 text-left">Name</th>
            <th class="py-3 px-6 text-left">Phone</th>
            <th class="py-3 px-6 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="text-gray-600 text-sm font-light">
          <tr v-for="customer in customers" :key="customer.id" class="border-b border-gray-200 hover:bg-gray-100">
            <td class="py-3 px-6 text-left whitespace-nowrap">{{ customer.id }}</td>
            <td class="py-3 px-6 text-left">{{ customer.customer_name }}</td>
            <td class="py-3 px-6 text-left">{{ customer.phone }}</td>
            <td class="py-3 px-6 text-center">
              <div class="flex item-center justify-center">
                <button @click="openModal(customer)" class="w-4 mr-2 transform hover:text-purple-500 hover:scale-110">
                  <!-- Edit Icon -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.536L16.732 3.732z" />
                  </svg>
                </button>
                <button @click="deleteCustomer(customer)" class="w-4 mr-2 transform hover:text-red-500 hover:scale-110">
                  <!-- Delete Icon -->
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add/Edit Modal -->
    <CustomerFormModal v-if="isModalOpen" :customer="editableCustomer" @close="closeModal" />

  </Layout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import Layout from '@/Components/Layout.vue';
import CustomerFormModal from '@/Components/CustomerFormModal.vue';

defineProps({
  customers: Array,
});

const isModalOpen = ref(false);
const editableCustomer = ref(null);

function openModal(customer = null) {
  editableCustomer.value = customer ? { ...customer } : null;
  isModalOpen.value = true;
}

function closeModal() {
  isModalOpen.value = false;
  editableCustomer.value = null;
}

function deleteCustomer(customer) {
  if (confirm(`Are you sure you want to delete ${customer.customer_name}?`)) {
    router.delete(`/wp-json/lottery/v1/customers/${customer.id}`, {
      preserveScroll: true,
      onSuccess: () => {
        // Maybe show a notification
      },
    });
  }
}
</script>