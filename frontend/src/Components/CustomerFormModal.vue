<template>
  <TransitionRoot appear :show="true" as="template">
    <Dialog as="div" @close="$emit('close')" class="relative z-10">
      <TransitionChild
        as="template"
        enter="duration-300 ease-out"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="duration-200 ease-in"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-black bg-opacity-25" />
      </TransitionChild>

      <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4 text-center">
          <TransitionChild
            as="template"
            enter="duration-300 ease-out"
            enter-from="opacity-0 scale-95"
            enter-to="opacity-100 scale-100"
            leave="duration-200 ease-in"
            leave-from="opacity-100 scale-100"
            leave-to="opacity-0 scale-95"
          >
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                {{ form.id ? 'Edit Customer' : 'Add New Customer' }}
              </DialogTitle>
              <form @submit.prevent="submit">
                <div class="mt-4">
                  <label for="customer_name" class="block text-sm font-medium text-gray-700">Name</label>
                  <input v-model="form.customer_name" type="text" id="customer_name" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required />
                  <div v-if="form.errors.customer_name" class="text-sm text-red-600">{{ form.errors.customer_name }}</div>
                </div>
                <div class="mt-4">
                  <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                  <input v-model="form.phone" type="text" id="phone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required />
                   <div v-if="form.errors.phone" class="text-sm text-red-600">{{ form.errors.phone }}</div>
                </div>

                <div class="mt-6 flex justify-end space-x-2">
                  <button type="button" @click="$emit('close')" class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2">
                    Cancel
                  </button>
                  <button type="submit" :disabled="form.processing" class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
                    {{ form.id ? 'Update' : 'Create' }}
                  </button>
                </div>
              </form>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import {
  TransitionRoot,
  TransitionChild,
  Dialog,
  DialogPanel,
  DialogTitle,
} from '@headlessui/vue';

const props = defineProps({
  customer: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
  id: props.customer?.id || null,
  customer_name: props.customer?.customer_name || '',
  phone: props.customer?.phone || '',
});

function submit() {
  const onSuccess = () => {
    form.reset();
    emit('close');
  };

  if (form.id) {
    form.put(`/wp-json/lottery/v1/customers/${form.id}`, {
      preserveScroll: true,
      onSuccess,
    });
  } else {
    form.post('/wp-json/lottery/v1/customers', {
      preserveScroll: true,
      onSuccess,
    });
  }
}
</script>