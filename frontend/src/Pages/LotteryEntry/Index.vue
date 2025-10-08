<template>
  <Layout>
    <h1 class="text-2xl font-bold mb-6">Lottery Entry</h1>
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-md">
      <form @submit.prevent="submit">
        <!-- Customer and Session Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
          <!-- Customer Autocomplete -->
          <div>
            <label class="block text-sm font-medium text-gray-700">Customer</label>
            <!-- Autocomplete component will go here -->
            <input
              v-model="form.customer_name"
              @input="searchCustomers"
              placeholder="Search by name or phone"
              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
              required
            />
            <!-- Search results dropdown -->
            <div v-if="showResults && searchResults.length" class="absolute z-10 w-full bg-white rounded-md shadow-lg mt-1">
              <ul>
                <li
                  v-for="customer in searchResults"
                  :key="customer.id"
                  @click="selectCustomer(customer)"
                  class="px-4 py-2 cursor-pointer hover:bg-gray-100"
                >
                  {{ customer.customer_name }} ({{ customer.phone }})
                </li>
              </ul>
            </div>
          </div>
          <!-- Draw Session -->
          <div>
            <label for="draw-session" class="block text-sm font-medium text-gray-700">Draw Session</label>
            <select v-model="form.draw_session" id="draw-session" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
              <option value="12:01 PM">12:01 PM</option>
              <option value="4:30 PM">4:30 PM</option>
            </select>
          </div>
        </div>

        <!-- Entry Rows Section -->
        <div v-for="(entry, index) in form.entries" :key="index" class="flex items-center space-x-2 mb-2">
          <input v-model="entry.number" type="text" placeholder="Num" class="w-20 text-center rounded-md border-gray-300" maxlength="2" pattern="\\d{2}" required />
          <input v-model="entry.amount" type="number" placeholder="Amount" class="w-32 text-center rounded-md border-gray-300" step="100" min="0" required />
          <label class="flex items-center">
            <input v-model="entry.is_reverse" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600" />
            <span class="ml-2 text-sm text-gray-600">Reverse</span>
          </label>
          <button @click="removeEntry(index)" type="button" class="text-red-500 hover:text-red-700">&times;</button>
        </div>

        <!-- Action Buttons -->
        <div class="mt-4">
          <button @click="addEntry" type="button" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-4 rounded">
            Add Row
          </button>
        </div>

        <hr class="my-6" />

        <!-- Submission -->
        <div class="flex justify-end">
          <button type="submit" :disabled="form.processing" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Submit Entries
          </button>
        </div>
      </form>
      <div v-if="form.wasSuccessful" class="mt-4 p-4 bg-green-100 text-green-800 rounded">
        Entries submitted successfully!
      </div>
      <div v-if="form.hasErrors" class="mt-4 p-4 bg-red-100 text-red-800 rounded">
        <p v-for="error in form.errors">{{ error }}</p>
      </div>
    </div>
  </Layout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import Layout from '@/Components/Layout.vue';
import axios from 'axios';
import { debounce } from 'lodash-es';

const form = useForm({
  customer_name: '',
  phone: '',
  draw_session: '12:01 PM', // Default session
  entries: [
    { number: '', amount: '', is_reverse: false },
  ],
});

const searchResults = ref([]);
const showResults = ref(false);

function addEntry() {
  form.entries.push({ number: '', amount: '', is_reverse: false });
}

function removeEntry(index) {
  form.entries.splice(index, 1);
}

const searchCustomers = debounce(async (event) => {
  const term = event.target.value;
  if (term.length < 2) {
    searchResults.value = [];
    showResults.value = false;
    return;
  }
  try {
    const response = await axios.get(`/wp-json/lottery/v1/customers/search?term=${term}`);
    searchResults.value = response.data;
    showResults.value = true;
  } catch (error) {
    console.error('Error searching customers:', error);
  }
}, 300);

function selectCustomer(customer) {
  form.customer_name = customer.customer_name;
  form.phone = customer.phone;
  searchResults.value = [];
  showResults.value = false;
}

function submit() {
  form.post('/wp-json/lottery/v1/entries', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('entries');
      addEntry(); // Add a fresh row back
    },
  });
}
</script>