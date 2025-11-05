<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Users, User, Mail, Phone, Building, Heart, MessageSquare } from 'lucide-vue-next'

interface Beneficiary {
    id: number
    first_name?: string
    last_name?: string
    phone?: string
    email?: string
    organization_name?: string
    relationship_to_donor?: string
    special_instructions?: string
    is_organization: boolean
}

const { beneficiary, hasBeneficiary } = defineProps<{
    beneficiary?: Beneficiary | null
    hasBeneficiary: boolean
}>()

// Helper function to get full name
const getFullName = (beneficiary: Beneficiary) => {
    if (beneficiary.is_organization) {
        return beneficiary.organization_name || 'Unknown Organization'
    }

    const firstName = beneficiary.first_name || ''
    const lastName = beneficiary.last_name || ''
    return `${firstName} ${lastName}`.trim() || 'Unknown Person'
}

// Helper function to get relationship display
const getRelationshipDisplay = (relationship: string) => {
    const relationships = {
        'family': 'Family Member',
        'friend': 'Friend',
        'self': 'Self',
        'organization': 'Organization',
        'other': 'Other'
    }
    return relationships[relationship] || relationship
}
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle class="flex items-center space-x-2">
                <Heart class="h-5 w-5 text-red-600" />
                <span>Donation Beneficiary</span>
            </CardTitle>
        </CardHeader>

        <CardContent>
            <!-- Has Beneficiary -->
            <div v-if="hasBeneficiary && beneficiary" class="space-y-4">
                <!-- Beneficiary Type & Name -->
                <div class="flex items-start space-x-3 p-4 bg-purple-50 rounded-lg">
                    <component :is="beneficiary.is_organization ? Building : User"
                               class="h-8 w-8 text-purple-600 mt-1" />
                    <div class="flex-1">
                        <div class="flex items-center space-x-2 mb-1">
                            <h4 class="font-semibold text-gray-900">{{ getFullName(beneficiary) }}</h4>
                            <span class="inline-flex items-center rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-800">
                                {{ beneficiary.is_organization ? 'Organization' : 'Individual' }}
                            </span>
                        </div>

                        <!-- Relationship (for individuals) -->
                        <p v-if="!beneficiary.is_organization && beneficiary.relationship_to_donor"
                           class="text-sm text-gray-600">
                            Relationship: {{ getRelationshipDisplay(beneficiary.relationship_to_donor) }}
                        </p>
                    </div>
                </div>

                <!-- Contact Information -->
                <div v-if="beneficiary.phone || beneficiary.email" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Phone -->
                    <div v-if="beneficiary.phone" class="flex items-center space-x-2">
                        <Phone class="h-4 w-4 text-gray-500" />
                        <span class="text-sm">{{ beneficiary.phone }}</span>
                    </div>

                    <!-- Email -->
                    <div v-if="beneficiary.email" class="flex items-center space-x-2">
                        <Mail class="h-4 w-4 text-gray-500" />
                        <span class="text-sm">{{ beneficiary.email }}</span>
                    </div>
                </div>

                <!-- Special Instructions -->
                <div v-if="beneficiary.special_instructions" class="mt-4">
                    <div class="flex items-start space-x-2">
                        <MessageSquare class="h-4 w-4 text-gray-500 mt-0.5" />
                        <div>
                            <p class="text-sm font-medium text-gray-700 mb-1">Special Instructions:</p>
                            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">{{ beneficiary.special_instructions }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- No Beneficiary -->
            <div v-else class="text-center py-6">
                <Users class="h-12 w-12 text-gray-400 mx-auto mb-3" />
                <h4 class="text-sm font-medium text-gray-900 mb-1">No Beneficiary Specified</h4>
                <p class="text-sm text-gray-600">The donor did not specify a beneficiary for this donation.</p>
            </div>
        </CardContent>
    </Card>
</template>
