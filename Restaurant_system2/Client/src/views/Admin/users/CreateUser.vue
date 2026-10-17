<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import DashboardLayout from '@/Layouts/DashboardLayout.vue'
import UserForm from '../../../components/user/UserForm.vue'
import { useUserStore } from '../../../stores/user'
import type { User } from '../../../types/user'

const router = useRouter()
const userStore = useUserStore()

const successMessage = ref('')
const createdUserEmail = ref('')
const hasErrors = computed(() => Object.keys(userStore.errors).length > 0)

const createUser = async (data: User) => {
  successMessage.value = ''
  try {
    await userStore.createUser(data)
    createdUserEmail.value = data.email
    successMessage.value = `User created successfully!`
    setTimeout(() => {
      router.push('/users')
    }, 3000)
  } catch (error: any) {
    console.error('[CreateUser] Error creating user:', error)
  }
}
</script>

<template>
  <DashboardLayout>
    <div class="w-full px-4 sm:px-0">
      <div
        class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 sm:mb-6 gap-3 sm:gap-0"
      >
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-800">Create User</h1>
          <p class="text-gray-500 text-sm sm:text-base mt-1">Add a new system user.</p>
        </div>

        <button
          @click="$router.back()"
          class="px-4 sm:px-5 py-2 rounded-lg bg-gray-200 hover:bg-gray-300 text-sm sm:text-base font-medium cursor-pointer"
        >
          Cancel
        </button>
      </div>

      <div class="bg-white rounded-xl shadow overflow-hidden">
        <div class="p-4 sm:p-6 lg:p-8">
          <!-- Success: Email Sent Confirmation -->
          <div v-if="successMessage" class="mb-6">
            <div class="p-6 bg-green-50 border border-green-200 rounded-xl text-center">
              <div class="flex justify-center mb-3">
                <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                  </svg>
                </div>
              </div>
              <h3 class="text-green-800 font-bold text-lg mb-1">✓ {{ successMessage }}</h3>
              <p class="text-green-700 text-sm">
                Login credentials have been automatically sent to
                <strong class="font-semibold">{{ createdUserEmail }}</strong>
              </p>
              <p class="text-green-600 text-xs mt-2">
                The user will receive their email and temporary password shortly.
              </p>
              <p class="text-slate-400 text-xs mt-3">Redirecting to users list...</p>
            </div>
          </div>

          <div v-if="hasErrors" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
            <h3 class="text-red-800 font-semibold mb-2 text-sm sm:text-base">
              Please fix the following errors:
            </h3>
            <ul class="list-disc list-inside text-red-600 text-xs sm:text-sm space-y-1">
              <li v-for="(messages, field) in userStore.errors" :key="field">
                <strong>{{ field }}:</strong> {{ messages[0] }}
              </li>
            </ul>
          </div>

          <UserForm
            v-if="!successMessage"
            :loading="userStore.loading"
            :errors="userStore.errors"
            :is-edit-mode="false"
            @submit="createUser"
          />
        </div>
      </div>
    </div>
  </DashboardLayout>
</template>
