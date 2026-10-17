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
const generatedPassword = ref('')
const createdUserEmail = ref('')
const passwordCopied = ref(false)
const hasErrors = computed(() => Object.keys(userStore.errors).length > 0)

const createUser = async (data: User) => {
  successMessage.value = ''
  generatedPassword.value = ''
  try {
    const result = await userStore.createUser(data)
    createdUserEmail.value = data.email
    generatedPassword.value = result.generated_password || ''
    successMessage.value = `User created successfully!`
    // Navigate after longer delay so admin can copy the password
    setTimeout(() => {
      router.push('/users')
    }, 15000)
  } catch (error: any) {
    console.error('[CreateUser] Error creating user:', error)
  }
}

const copyPassword = async () => {
  if (!generatedPassword.value) return
  try {
    await navigator.clipboard.writeText(generatedPassword.value)
    passwordCopied.value = true
    setTimeout(() => { passwordCopied.value = false }, 2000)
  } catch {
    // fallback
    const el = document.createElement('textarea')
    el.value = generatedPassword.value
    document.body.appendChild(el)
    el.select()
    document.execCommand('copy')
    document.body.removeChild(el)
    passwordCopied.value = true
    setTimeout(() => { passwordCopied.value = false }, 2000)
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
          <!-- Success + Generated Password Box -->
          <div v-if="successMessage" class="mb-6 space-y-4">
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
              <p class="text-green-800 font-semibold text-sm sm:text-base">✓ {{ successMessage }}</p>
              <p class="text-green-600 text-xs mt-1">Redirecting to users list in 15 seconds...</p>
            </div>

            <!-- Generated Password Panel -->
            <div v-if="generatedPassword" class="p-5 bg-amber-50 border-2 border-amber-400 rounded-xl">
              <div class="flex items-center gap-2 mb-3">
                <span class="text-xl">🔑</span>
                <h3 class="font-bold text-amber-800 text-base">Generated Password — Save This Now!</h3>
              </div>
              <p class="text-amber-700 text-sm mb-3">
                An email was sent to <strong>{{ createdUserEmail }}</strong> with these credentials.
                Copy the password below in case the email is delayed.
              </p>
              <div class="flex items-center gap-3 bg-white border-2 border-amber-300 rounded-lg p-3">
                <code class="flex-1 text-lg font-mono font-bold text-slate-800 tracking-wider select-all">
                  {{ generatedPassword }}
                </code>
                <button
                  @click="copyPassword"
                  class="px-4 py-2 rounded-lg text-sm font-bold transition-all cursor-pointer"
                  :class="passwordCopied
                    ? 'bg-green-500 text-white'
                    : 'bg-amber-500 hover:bg-amber-600 text-white'"
                >
                  {{ passwordCopied ? '✓ Copied!' : 'Copy' }}
                </button>
              </div>
              <p class="text-amber-600 text-xs mt-2 font-medium">
                ⚠️ This password won't be shown again after you leave this page.
              </p>
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
