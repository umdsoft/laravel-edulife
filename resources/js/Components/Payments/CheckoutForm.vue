<script setup>
/**
 * CheckoutForm — pay for one product online with Payme or Click.
 * The price (and any promo code) is computed on the server (POST payments.quote);
 * "To'lash" creates the order (POST payments.checkout) and the server redirects the
 * browser to the provider. Docs: docs/payments/PAYMENTS.md
 *
 *   <CheckoutForm product-type="course" :product-id="course.id" />
 */
import { computed, onMounted, ref, useId } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { CheckCircleIcon, LockClosedIcon, TagIcon } from '@heroicons/vue/20/solid';
import { Alert, Badge, Button, Input, Skeleton } from '@/Components/UI';

const props = defineProps({
    productType: { type: String, required: true }, // course | coin_package | subscription | olympiad_registration
    productId: { type: String, required: true },
    allowPromo: { type: Boolean, default: true },
    submitLabel: { type: String, default: null },
});

const PROVIDERS = {
    payme: { name: 'Payme', hint: 'Uzcard, Humo kartasi yoki Payme ilovasi' },
    click: { name: 'Click', hint: 'Uzcard, Humo kartasi yoki Click ilovasi' },
};

const uid = useId();
const quote = ref(null);
const loading = ref(true);
const error = ref('');
const promoInput = ref('');
const promoError = ref('');
const promoBusy = ref(false);
const appliedPromo = ref(null);
const provider = ref(null);
const submitting = ref(false);

const providers = computed(() => (quote.value?.providers || []).filter((p) => PROVIDERS[p]));
const groupDigits = (value) => String(Math.round(Number(value) || 0)).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');

const fetchQuote = async (promoCode = null) => {
    const { data } = await axios.post(route('payments.quote'), {
        product_type: props.productType,
        product_id: props.productId,
        promo_code: promoCode,
    });
    return data;
};

onMounted(async () => {
    try {
        quote.value = await fetchQuote();
        provider.value = providers.value[0] || null;
    } catch (e) {
        error.value = e.response?.data?.message || "Narxni yuklab bo'lmadi. Sahifani yangilab ko'ring.";
    } finally {
        loading.value = false;
    }
});

const applyPromo = async () => {
    const code = promoInput.value.trim();
    if (!code) return;
    promoBusy.value = true;
    promoError.value = '';
    try {
        quote.value = await fetchQuote(code);
        appliedPromo.value = quote.value.promo_code || code.toUpperCase();
    } catch (e) {
        promoError.value = e.response?.data?.message || e.response?.data?.errors?.promo_code?.[0] || "Promokod qo'llanmadi.";
    } finally {
        promoBusy.value = false;
    }
};

const removePromo = async () => {
    appliedPromo.value = null;
    promoInput.value = '';
    promoError.value = '';
    try {
        quote.value = await fetchQuote();
    } catch {
        // keep the last quote
    }
};

const submit = () => {
    if (!provider.value) return;
    error.value = '';
    router.post(route('payments.checkout'), {
        product_type: props.productType,
        product_id: props.productId,
        provider: provider.value,
        promo_code: appliedPromo.value,
    }, {
        preserveScroll: true,
        preserveState: true,
        onStart: () => (submitting.value = true),
        onError: (errors) => {
            error.value = errors.checkout || errors.provider || errors.promo_code || Object.values(errors)[0] || "To'lovni boshlab bo'lmadi.";
            submitting.value = false;
        },
        // on success the browser leaves for Payme / Click, keep the spinner until then
        onCancel: () => (submitting.value = false),
    });
};

const buttonLabel = computed(() => props.submitLabel
    || (provider.value ? `${PROVIDERS[provider.value].name} orqali to'lash` : "To'lash"));
</script>

<template>
    <div class="space-y-5">
        <div v-if="loading" class="space-y-3" aria-busy="true">
            <Skeleton class="h-5 w-2/3" />
            <Skeleton class="h-16 w-full" />
            <Skeleton class="h-10 w-full" />
        </div>

        <Alert v-else-if="!quote" variant="danger">{{ error }}</Alert>

        <template v-else>
            <dl class="divide-y divide-border rounded-md border border-border px-3 text-sm">
                <div class="flex justify-between gap-3 py-2.5">
                    <dt class="text-fg-muted">Narxi</dt>
                    <dd class="tabular-nums text-fg">{{ groupDigits(quote.original) }} so'm</dd>
                </div>
                <div v-if="quote.discount > 0" class="flex justify-between gap-3 py-2.5">
                    <dt class="text-success">Chegirma</dt>
                    <dd class="tabular-nums text-success">−{{ groupDigits(quote.discount) }} so'm</dd>
                </div>
                <div class="flex justify-between gap-3 py-2.5 text-base font-semibold">
                    <dt class="text-fg">To'lanadi</dt>
                    <dd class="tabular-nums text-fg">{{ groupDigits(quote.amount) }} so'm</dd>
                </div>
            </dl>

            <div v-if="allowPromo">
                <div v-if="appliedPromo" class="flex items-center justify-between gap-3">
                    <Badge variant="success" :icon="TagIcon">{{ appliedPromo }}</Badge>
                    <Button variant="link" size="sm" @click="removePromo">Olib tashlash</Button>
                </div>
                <form v-else class="flex items-end gap-2" @submit.prevent="applyPromo">
                    <Input v-model="promoInput" label="Promokod" optional placeholder="Masalan, YOZ25" maxlength="50"
                        autocomplete="off" :error="promoError" class="flex-1" />
                    <Button type="submit" variant="secondary" :loading="promoBusy" :disabled="!promoInput.trim()"
                        :class="promoError ? 'mb-6' : ''">Qo'llash</Button>
                </form>
            </div>

            <fieldset v-if="providers.length" class="space-y-2">
                <legend class="mb-2 text-sm font-medium text-fg">To'lov usuli</legend>
                <label v-for="key in providers" :key="key" :for="`${uid}-${key}`"
                    :class="['flex cursor-pointer items-center gap-3 rounded-md border px-3 py-3 transition-colors duration-150',
                        provider === key ? 'border-brand-600 bg-brand-50 ring-1 ring-brand-600' : 'border-border-strong hover:bg-surface-2']">
                    <input :id="`${uid}-${key}`" v-model="provider" type="radio" :name="`${uid}-provider`" :value="key"
                        class="h-4 w-4 accent-brand-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2" />
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold text-fg">{{ PROVIDERS[key].name }}</span>
                        <span class="block text-13 text-fg-subtle">{{ PROVIDERS[key].hint }}</span>
                    </span>
                    <CheckCircleIcon v-if="provider === key" class="h-5 w-5 shrink-0 text-brand" aria-hidden="true" />
                </label>
            </fieldset>
            <Alert v-else variant="warning" title="To'lov tizimlari sozlanmagan">
                Payme va Click hali ulanmagan. To'lov uchun administrator bilan bog'laning.
            </Alert>

            <Alert v-if="error" variant="danger">{{ error }}</Alert>

            <div v-if="providers.length" class="space-y-2">
                <Button block size="lg" :loading="submitting" :disabled="!provider" :icon-left="LockClosedIcon" @click="submit">
                    {{ buttonLabel }}
                </Button>
                <p class="text-center text-13 text-fg-subtle">
                    Siz {{ provider ? PROVIDERS[provider].name : "to'lov tizimi" }} sahifasiga o'tasiz. To'lovdan so'ng shu yerga qaytasiz.
                </p>
            </div>
        </template>
    </div>
</template>
