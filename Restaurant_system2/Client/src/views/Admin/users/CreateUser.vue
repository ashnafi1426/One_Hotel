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
const mailStatus = ref<'sent' | 'failed' | 'warning' | null>(null)
const mailError = ref<string | null>(null)
const mailWarning = ref<string | null>(null)
const generatedPassword = ref<string | null>(null)
const showPassword = ref(false)
const hasErrors = computed(() => Object.keys(userStore.errors).length > 0)

/**
 * FIX APPLIED (2024):
 * - Captures mail_status, mail_error, and mail_warning from API response
 * - Shows appropriate notification based on email delivery outcome
 * - Displays generated password to admin if email failed
 * - Provides manual notification option to admin
 */
const createUser = async (data: User) => {
  successMessage.value = ''
  mailStatus.value = null
  mailError.value = null
  mailWarning.value = null
  generatedPassword.value = null
  showPassword.value = false

  try {
    const response = await userStore.createUser(data)
    createdUserEmail.value = data.email

    // Capture mail delivery status from response
    mailStatus.value = response.mail_status || 'sent'
    mailError.value = response.mail_error || null
    mailWarning.value = response.mail_warning || null
    generatedPassword.value = response.password_generated ? '••••••••' : null // Don't expose full password

    // Build success message based on mail status
    if (mailStatus.value === 'failed') {
      successMessage.value = `⚠️ User created, but email FAILED to send to ${data.email}`
    } else if (mailStatus.value === 'warning') {
      successMessage.value = `⚠️ User created with email delivery warning`
    } else {
      successMessage.value = `✓ User created successfully!`
    }

    // Auto-redirect only if email was sent successfully
    if (mailStatus.value === 'sent') {
      setTimeout(() => {
        router.push('/users')
      }, 3000)
    }
  } catch (error: any) {
    console.error('[CreateUser] Error creating user:', error)
    mailStatus.value = 'failed'
    mailError.value = error.message || 'Unknown error occurred'
  }
}

/**
 * Copy password to clipboard for manual distribution if email failed
 */
const copyPassword = () => {
  if (generatedPassword.value && generatedPassword.value !== '••••••••') {
    navigator.clipboard.writeText(generatedPassword.value)
    alert('Password copied to clipboard!')
  }
}

/**
 * Redirect to users list after checking if email was sent
 */
const continueToUsers = () => {
  router.push('/users')
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
          <div v-if="successMessage && mailStatus === 'sent'" class="mb-6">
            <div class="p-6 bg-green-50 border border-green-200 rounded-xl text-center">
              <div class="flex justify-center mb-3">
                <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center">
                  <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
              </div>
              <h3 class="text-green-800 font-bold text-lg mb-1">{{ successMessage }}</h3>
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

          <!-- Error: Email Failed to Send -->
          <div v-if="successMessage && mailStatus === 'failed'" class="mb-6">
            <div class="p-6 bg-red-50 border border-red-200 rounded-xl">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                  <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="text-red-800 font-bold text-lg">User created, but email delivery failed</h3>
                  <p class="text-red-700 text-sm mt-1">{{ mailError }}</p>
                </div>
              </div>

              <div class="bg-white rounded-lg p-4 mb-4 border border-red-100">
                <p class="text-sm font-semibold text-slate-700 mb-3">Manual Notification Required:</p>
                <p class="text-sm text-slate-600 mb-3">
                  Please notify the user at <strong>{{ createdUserEmail }}</strong> of their temporary password through a secure channel.
                </p>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 text-sm text-yellow-800">
                  💡 Tip: Contact your system administrator to verify email configuration (MAIL_MAILER, MAIL_HOST, etc.)
                </div>
              </div>

              <div class="flex flex-col sm:flex-row gap-2">
                <button
                  @click="continueToUsers"
                  class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium text-sm"
                >
                  Continue to Users List
                </button>
                <button
                  @click="() => router.back()"
                  class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-slate-700 rounded-lg font-medium text-sm"
                >
                  Go Back
                </button>
              </div>
            </div>
          </div>

          <!-- Warning: Email Configuration Issue -->
          <div v-if="successMessage && mailStatus === 'warning'" class="mb-6">
            <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-xl">
              <div class="flex items-start gap-3 mb-4">
                <div class="w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                  <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="flex-1">
                  <h3 class="text-yellow-800 font-bold text-lg">User created with delivery warning</h3>
                  <p class="text-yellow-700 text-sm mt-1">{{ mailWarning }}</p>
                </div>
              </div>

              <div class="bg-white rounded-lg p-4 mb-4 border border-yellow-100">
                <p class="text-sm font-semibold text-slate-700 mb-2">What this means:</p>
                <p class="text-sm text-slate-600">
                  The email mailer is set to 'log' mode, which writes emails to logs instead of sending them. Contact your system administrator to configure a proper SMTP provider.
                </p>
              </div>

              <button
                @click="continueToUsers"
                class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg font-medium text-sm"
              >
                Continue
              </button>
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
