<template>
  <div class="merchant-settings-layout">
    <section class="card admin-card merchant-settings-card">
      <div class="admin-card-header">
        <div><span class="admin-eyebrow">Account controls</span><h2>Access and verification</h2><p>Control whether the merchant can operate on Pahatud.</p></div>
      </div>
      <div class="merchant-settings-list">
        <setting-row title="Account active" description="Allows this merchant account to operate on the marketplace." :enabled="merchant.active" :busy="isBusy('active')" :disabled="accountControlsLocked" @toggle="toggleSetting('active', 'status')" />
        <setting-row title="Verified" description="Marks the merchant as reviewed and verified by Pahatud." :enabled="merchant.isverified" :busy="isBusy('isverified')" :disabled="accountControlsLocked" @toggle="toggleSetting('isverified', 'verify')" />
      </div>
      <div v-if="requiresReview" class="merchant-settings-note"><i class="fas fa-info-circle"></i><span>{{ canManageAccountControls ? 'This merchant is managed through application review. As an administrator, you can override its activation and verification here.' : 'Account activation and verification are managed from this merchant’s application review.' }}</span></div>
    </section>

    <section class="card admin-card merchant-settings-card">
      <div class="admin-card-header">
        <div><span class="admin-eyebrow">Store capabilities</span><h2>Ordering settings</h2><p>Choose the ordering features available in the merchant dashboard.</p></div>
      </div>
      <div class="merchant-settings-list">
        <setting-row title="Store open" description="Allows customers to place orders while the store is available." :enabled="merchant.istoreopen" :busy="isBusy('istoreopen')" @toggle="toggleSetting('istoreopen', 'online')" />
        <setting-row title="QR ordering" description="Shows table QR codes and enables Kitchen Display and Cashier Dashboard in the merchant workspace." :enabled="merchant.qr_ordering_enabled" :busy="isBusy('qr_ordering_enabled')" @toggle="toggleSetting('qr_ordering_enabled', 'qr-ordering')" />
        <setting-row title="Pre-order" description="Allows customers to place eligible orders ahead of time." :enabled="merchant.is_pre_order" :busy="isBusy('is_pre_order')" @toggle="toggleSetting('is_pre_order', 'preorder')" />
        <setting-row title="Commission discount" description="Applies the configured Pahatud commission discount for this merchant." :enabled="merchant.pahatud_comm_discount" :busy="isBusy('pahatud_comm_discount')" @toggle="toggleSetting('pahatud_comm_discount', 'coomrate')" />
        <setting-row title="Add-Up Settings" description="Adds configured merchant charges to eligible item pricing." :enabled="merchant.addup" :busy="isBusy('addup')" @toggle="toggleSetting('addup', 'addup')" />
      </div>
      <div class="merchant-settings-note" :class="{ enabled: merchant.qr_ordering_enabled }"><i class="fas" :class="merchant.qr_ordering_enabled ? 'fa-check-circle' : 'fa-lock'"></i><span>{{ merchant.qr_ordering_enabled ? 'QR ordering tools are available to this merchant.' : 'QR codes, Kitchen Display, and Cashier Dashboard are unavailable until QR ordering is enabled.' }}</span></div>
    </section>
  </div>
</template>

<script>
const SettingRow = {
  props: {
    title: { type: String, required: true },
    description: { type: String, required: true },
    enabled: { type: Boolean, required: true },
    busy: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
  },
  template: `
    <div class="merchant-setting-row">
      <div><strong>{{ title }}</strong><p>{{ description }}</p></div>
      <label class="merchant-setting-toggle">
        <input type="checkbox" :checked="enabled" :disabled="busy || disabled" :aria-label="title" @change="$emit('toggle')">
        <i></i><span>{{ busy ? 'Saving…' : enabled ? 'Enabled' : 'Disabled' }}</span>
      </label>
    </div>
  `,
};

export default {
  components: { SettingRow },
  props: {
    initialMerchant: { type: Object, required: true },
    requiresReview: { type: Boolean, default: false },
    canManageAccountControls: { type: Boolean, default: false },
  },
  data() {
    return {
      merchant: { ...this.initialMerchant },
      busy: {},
    };
  },
  computed: {
    accountControlsLocked() {
      return this.requiresReview && !this.canManageAccountControls;
    },
  },
  methods: {
    toggleSetting(field, endpoint) {
      if (this.isBusy(field)) return;
      const previous = this.merchant[field];
      this.$set(this.merchant, field, !previous);
      this.$set(this.busy, field, true);

      axios.post(`/api/data/merchant/${this.merchant.id}/${endpoint}/submit?api_token=${api_token}`)
        .then((response) => {
          if (!response.data.status) {
            this.merchant[field] = previous;
            toastr.info(response.data.message);
            return;
          }
          toastr.success(response.data.message);
        })
        .catch((error) => {
          this.merchant[field] = previous;
          toastr.error(error.response?.data?.message || 'Unable to update this merchant setting.');
        })
        .finally(() => this.$delete(this.busy, field));
    },
    isBusy(field) {
      return Boolean(this.busy[field]);
    },
  },
};
</script>

<style>
.merchant-settings-layout { display: grid; gap: 20px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
.merchant-settings-card { margin: 0; overflow: hidden; }
.merchant-settings-card .admin-card-header h2 { font-size: 20px; margin: 3px 0; }
.merchant-settings-list { display: grid; }
.merchant-setting-row { align-items: center; border-top: 1px solid #f0ece8; display: flex; gap: 24px; justify-content: space-between; min-height: 92px; padding: 18px 22px; }
.merchant-setting-row strong { color: #252b2e; display: block; font-size: 14px; }
.merchant-setting-row p { color: #777f83; font-size: 11px; line-height: 1.5; margin: 4px 0 0; max-width: 480px; }
.merchant-setting-toggle { align-items: center; cursor: pointer; display: grid; flex: 0 0 auto; gap: 8px; grid-template-columns: 46px 58px; margin: 0; }
.merchant-setting-toggle input { height: 0; opacity: 0; position: absolute; width: 0; }
.merchant-setting-toggle i { background: #cfd4d7; border-radius: 99px; box-shadow: inset 0 0 0 1px rgba(42,49,53,.08); height: 24px; position: relative; transition: background .2s ease; width: 46px; }
.merchant-setting-toggle i::after { background: #fff; border-radius: 50%; box-shadow: 0 2px 5px rgba(0,0,0,.24); content: ''; height: 20px; left: 2px; position: absolute; top: 2px; transition: transform .2s ease; width: 20px; }
.merchant-setting-toggle input:checked + i { background: #d5322f; }
.merchant-setting-toggle input:checked + i::after { transform: translateX(22px); }
.merchant-setting-toggle input:focus-visible + i { outline: 3px solid rgba(213,50,47,.25); outline-offset: 3px; }
.merchant-setting-toggle input:disabled + i { cursor: not-allowed; opacity: .5; }
.merchant-setting-toggle span { color: #687075; font-size: 10px; font-weight: 800; }
.merchant-settings-note { align-items: center; background: #fff8e8; border-top: 1px solid #eed9a5; color: #6d5521; display: flex; font-size: 11px; gap: 9px; padding: 13px 22px; }
.merchant-settings-note.enabled { background: #eaf8ef; border-color: #c9e7d4; color: #1c6b3a; }
@media (max-width: 1050px) { .merchant-settings-layout { grid-template-columns: 1fr; } }
@media (max-width: 575px) { .merchant-setting-row { align-items: flex-start; flex-direction: column; gap: 14px; } }
</style>
