<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import StoreSettingsTabs from '@/Components/Admin/StoreSettingsTabs.vue';
import {
    SparklesIcon,
    ArrowLeftIcon,
    ArrowPathIcon,
    PlusIcon,
    TrashIcon,
    EyeIcon,
    EyeSlashIcon,
    Bars3Icon,
    MagnifyingGlassIcon,
    TagIcon,
} from '@heroicons/vue/24/outline';
import { CheckIcon } from '@heroicons/vue/24/solid';
import draggable from 'vuedraggable';

const props = defineProps({
    module: Object,
    stores: Array,
    storeSettings: Object,
    defaultSettings: Object,
    specialProducts: Array,
    products: Array,
});

const storeTabsRef = ref(null);
const saving = ref(false);
const searchQuery = ref('');
const showProductSelector = ref(false);
const newProduct = ref({
    product_id: null,
    badge_text: '',
    badge_color: '#EF4444',
    starts_at: '',
    ends_at: '',
});

const submit = () => {
    if (!storeTabsRef.value) return;

    saving.value = true;
    router.put(route('admin.general.special.settings.update'), {
        store_id: storeTabsRef.value.activeStoreId,
        is_enabled: storeTabsRef.value.isEnabled,
        settings: storeTabsRef.value.localSettings,
    }, {
        preserveScroll: true,
        onFinish: () => saving.value = false,
    });
};

const resetAll = () => {
    if (confirm('Reset all settings to defaults?') && storeTabsRef.value) {
        Object.assign(storeTabsRef.value.localSettings, props.defaultSettings);
    }
};

const hasChanges = computed(() => {
    if (!storeTabsRef.value) return false;
    const currentStoreSettings = props.storeSettings[storeTabsRef.value.activeStoreId];
    if (!currentStoreSettings) return true;
    const original = { ...props.defaultSettings, ...(currentStoreSettings.settings || {}) };
    return JSON.stringify(storeTabsRef.value.localSettings) !== JSON.stringify(original) ||
           storeTabsRef.value.isEnabled !== currentStoreSettings.is_enabled;
});

const availableProducts = computed(() => {
    const selectedIds = props.specialProducts.map(sp => sp.product_id);
    return props.products.filter(p => {
        const notSelected = !selectedIds.includes(p.id);
        const matchesSearch = !searchQuery.value ||
            p.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            p.sku?.toLowerCase().includes(searchQuery.value.toLowerCase());
        return notSelected && matchesSearch;
    });
});

const selectProduct = (product) => {
    newProduct.value.product_id = product.id;
    newProduct.value.product_name = product.name;
};

const addProduct = () => {
    if (!newProduct.value.product_id) return;

    router.post(route('admin.general.special.products.add'), {
        product_id: newProduct.value.product_id,
        badge_text: newProduct.value.badge_text || null,
        badge_color: newProduct.value.badge_color || null,
        starts_at: newProduct.value.starts_at || null,
        ends_at: newProduct.value.ends_at || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showProductSelector.value = false;
            searchQuery.value = '';
            newProduct.value = {
                product_id: null,
                badge_text: '',
                badge_color: '#EF4444',
                starts_at: '',
                ends_at: '',
            };
        },
    });
};

const removeProduct = (specialProduct) => {
    if (confirm('Remove this product from special offers?')) {
        router.delete(route('admin.general.special.products.remove', specialProduct.id), {
            preserveScroll: true,
        });
    }
};

const toggleProduct = (specialProduct) => {
    router.post(route('admin.general.special.products.toggle', specialProduct.id), {}, {
        preserveScroll: true,
    });
};

const onDragEnd = () => {
    const productsOrder = props.specialProducts.map((sp, index) => ({
        id: sp.id,
        sort_order: index,
    }));

    router.post(route('admin.general.special.products.reorder'), {
        products: productsOrder,
    }, {
        preserveScroll: true,
    });
};

const badgePresets = [
    { text: 'Sale', color: '#EF4444' },
    { text: 'Hot', color: '#F97316' },
    { text: '-50%', color: '#EC4899' },
    { text: 'New', color: '#10B981' },
];
</script>

<template>
    <AdminLayout :title="`${module.name} Settings`">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('admin.modules.installed.index')"
                        class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <ArrowLeftIcon class="w-5 h-5" />
                    </Link>
                    <div class="flex items-center space-x-3">
                        <div class="p-3 bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl shadow-lg shadow-rose-500/25">
                            <SparklesIcon class="w-6 h-6 text-white" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-gray-900">Special Offers</h1>
                            <p class="text-sm text-gray-500">Highlight promotional products</p>
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span v-if="hasChanges" class="flex items-center text-sm text-amber-600 font-medium">
                        <span class="w-2 h-2 bg-amber-500 rounded-full mr-2 animate-pulse"></span>
                        Unsaved changes
                    </span>
                    <button
                        type="button"
                        @click="resetAll"
                        class="px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-colors"
                    >
                        <ArrowPathIcon class="w-4 h-4 inline mr-2" />
                        Reset
                    </button>
                    <button
                        type="button"
                        @click="submit"
                        :disabled="saving || !hasChanges"
                        class="px-6 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-rose-500 to-pink-600 rounded-xl hover:from-rose-600 hover:to-pink-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-lg shadow-rose-500/25"
                    >
                        <CheckIcon class="w-4 h-4 inline mr-2" />
                        {{ saving ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </div>
        </template>

        <StoreSettingsTabs ref="storeTabsRef" :stores="stores" :store-settings="storeSettings" :default-settings="defaultSettings" module-slug="special-product-general">
            <template #default="{ store, settings, updateSetting, isEnabled }">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Settings Form -->
                    <div class="lg:col-span-1 space-y-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                                <h3 class="font-semibold text-gray-900">Settings</h3>
                            </div>
                            <div class="p-6 space-y-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="font-medium text-gray-900">Enabled</h4>
                                        <p class="text-sm text-gray-500">Show special offers section</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="updateSetting('enabled', !settings.enabled)"
                                        :class="settings.enabled ? 'bg-green-500' : 'bg-gray-300'"
                                        class="relative inline-flex h-7 w-12 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors"
                                    >
                                        <span
                                            :class="settings.enabled ? 'translate-x-5' : 'translate-x-0'"
                                            class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg transition"
                                        />
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Section Title</label>
                                    <input
                                        type="text"
                                        :value="settings.title"
                                        @input="updateSetting('title', $event.target.value)"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Max Products</label>
                                    <input
                                        type="number"
                                        :value="settings.max_products"
                                        @input="updateSetting('max_products', parseInt($event.target.value) || 0)"
                                        min="1"
                                        max="50"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500 focus:border-rose-500"
                                    />
                                </div>
                            </div>
                        </div>

                        <div class="bg-rose-50 rounded-2xl p-5 border border-rose-100">
                            <h4 class="text-sm font-medium text-rose-900 mb-2">Tips</h4>
                            <p class="text-sm text-rose-700">
                                Add badges and schedule offers. Products will automatically hide when their end date passes.
                            </p>
                        </div>
                    </div>

                    <!-- Products Selection -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <h3 class="font-semibold text-gray-900">Special Products</h3>
                                    <span class="px-2 py-0.5 text-xs font-medium bg-rose-100 text-rose-700 rounded-full">
                                        {{ specialProducts.length }}
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="showProductSelector = !showProductSelector"
                                    class="px-4 py-2 text-sm font-medium text-white bg-gradient-to-r from-rose-500 to-pink-600 rounded-xl hover:from-rose-600 hover:to-pink-700 transition-all"
                                >
                                    <PlusIcon class="w-4 h-4 inline mr-2" />
                                    Add Product
                                </button>
                            </div>

                            <!-- Product Selector -->
                            <div v-if="showProductSelector" class="p-4 border-b border-gray-200 bg-rose-50">
                                <div v-if="!newProduct.product_id">
                                    <div class="relative mb-3">
                                        <MagnifyingGlassIcon class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                        <input
                                            type="text"
                                            v-model="searchQuery"
                                            placeholder="Search products..."
                                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-rose-500"
                                        />
                                    </div>
                                    <div class="max-h-48 overflow-y-auto space-y-1">
                                        <button
                                            v-for="product in availableProducts.slice(0, 10)"
                                            :key="product.id"
                                            type="button"
                                            @click="selectProduct(product)"
                                            class="w-full text-left px-3 py-2 text-sm hover:bg-rose-100 rounded-lg flex items-center justify-between"
                                        >
                                            <span>{{ product.name }}</span>
                                            <span class="text-gray-400 text-xs">{{ product.sku }}</span>
                                        </button>
                                    </div>
                                </div>

                                <div v-else class="space-y-4">
                                    <div class="flex items-center justify-between bg-white rounded-xl p-3">
                                        <span class="font-medium">{{ newProduct.product_name }}</span>
                                        <button @click="newProduct.product_id = null" class="text-sm text-rose-600">Change</button>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge Text</label>
                                            <input
                                                type="text"
                                                v-model="newProduct.badge_text"
                                                placeholder="e.g., Sale, -50%"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Badge Color</label>
                                            <div class="flex items-center space-x-2">
                                                <input
                                                    type="color"
                                                    v-model="newProduct.badge_color"
                                                    class="w-10 h-10 rounded-lg border cursor-pointer"
                                                />
                                                <div class="flex space-x-1">
                                                    <button
                                                        v-for="preset in badgePresets"
                                                        :key="preset.text"
                                                        type="button"
                                                        @click="newProduct.badge_text = preset.text; newProduct.badge_color = preset.color"
                                                        class="px-2 py-1 text-xs text-white rounded"
                                                        :style="{ backgroundColor: preset.color }"
                                                    >
                                                        {{ preset.text }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                                            <input
                                                type="datetime-local"
                                                v-model="newProduct.starts_at"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                                            <input
                                                type="datetime-local"
                                                v-model="newProduct.ends_at"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                                            />
                                        </div>
                                    </div>

                                    <button
                                        type="button"
                                        @click="addProduct"
                                        class="w-full px-4 py-2 text-sm font-medium text-white bg-rose-500 rounded-xl hover:bg-rose-600"
                                    >
                                        Add to Special Offers
                                    </button>
                                </div>
                            </div>

                            <div class="p-6">
                                <!-- Empty State -->
                                <div v-if="specialProducts.length === 0" class="text-center py-8">
                                    <SparklesIcon class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                                    <p class="text-gray-500">No special products yet</p>
                                    <p class="text-sm text-gray-400">Click "Add Product" to get started</p>
                                </div>

                                <!-- Products List -->
                                <draggable
                                    v-else
                                    :list="specialProducts"
                                    item-key="id"
                                    handle=".drag-handle"
                                    @end="onDragEnd"
                                    class="space-y-3"
                                >
                                    <template #item="{ element: sp }">
                                        <div class="flex items-center bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition-colors">
                                            <div class="drag-handle cursor-grab active:cursor-grabbing p-2 text-gray-400 hover:text-gray-600">
                                                <Bars3Icon class="w-5 h-5" />
                                            </div>

                                            <div class="flex-1 ml-3">
                                                <div class="flex items-center space-x-2">
                                                    <h4 class="font-medium text-gray-900">{{ sp.product?.name }}</h4>
                                                    <span
                                                        v-if="sp.badge_text"
                                                        class="px-2 py-0.5 text-xs font-bold text-white rounded"
                                                        :style="{ backgroundColor: sp.badge_color || '#EF4444' }"
                                                    >
                                                        {{ sp.badge_text }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-500">
                                                    <span v-if="sp.starts_at || sp.ends_at">
                                                        {{ sp.starts_at ? new Date(sp.starts_at).toLocaleDateString() : 'Now' }}
                                                        -
                                                        {{ sp.ends_at ? new Date(sp.ends_at).toLocaleDateString() : 'No end' }}
                                                    </span>
                                                    <span v-else>No schedule</span>
                                                </p>
                                            </div>

                                            <div class="flex items-center space-x-2">
                                                <button
                                                    @click="toggleProduct(sp)"
                                                    class="p-2 rounded-lg transition-colors"
                                                    :class="sp.is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-200'"
                                                >
                                                    <EyeIcon v-if="sp.is_active" class="w-5 h-5" />
                                                    <EyeSlashIcon v-else class="w-5 h-5" />
                                                </button>
                                                <button
                                                    @click="removeProduct(sp)"
                                                    class="p-2 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                                >
                                                    <TrashIcon class="w-5 h-5" />
                                                </button>
                                            </div>
                                        </div>
                                    </template>
                                </draggable>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </StoreSettingsTabs>
    </AdminLayout>
</template>