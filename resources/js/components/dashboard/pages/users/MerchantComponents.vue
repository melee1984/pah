<template>
  <div class="card admin-card dashboard-data-card">
    <div class="admin-card-header">
      <div>
        <h2>All merchant partners</h2>
        <p>{{ members.total || 0 }} registered {{ pluralize('restaurant', members.total || 0) }}</p>
      </div>
      <label class="admin-search dashboard-search">
        <i class="fas fa-search"></i>
        <input v-model="search" type="search" placeholder="Search restaurant, email, mobile, or city" aria-label="Search merchant partners">
        <button v-if="search" type="button" aria-label="Clear search" @click="search = ''">&times;</button>
      </label>
    </div>

    <div class="dashboard-table-controls">
      <ul class="nav nav-tabs dashboard-table-tabs" role="tablist" aria-label="Merchant account filters">
        <li v-for="tab in tabs" :key="tab.value" class="nav-item">
          <button
            type="button"
            class="nav-link"
            :class="{ active: activeTab === tab.value }"
            :aria-selected="activeTab === tab.value ? 'true' : 'false'"
            role="tab"
            @click="selectTab(tab.value)"
          >
            {{ tab.label }} <span class="badge ml-1" :class="tab.badgeClass">{{ counts[tab.value] || 0 }}</span>
          </button>
        </li>
      </ul>
    </div>

    <div v-if="loading" class="dashboard-loading"><i class="fas fa-circle-notch fa-spin"></i> Loading merchant partners…</div>

    <div v-else-if="merchantRows.length" class="table-responsive">
      <table class="table dashboard-data-table dashboard-merchant-table">
        <colgroup><col class="merchant-col-name"><col class="merchant-col-contact"><col class="merchant-col-location"><col class="merchant-col-account"><col class="merchant-col-settings"><col class="merchant-col-actions"></colgroup>
        <thead>
          <tr>
            <th>Merchant partner</th>
            <th>Contact</th>
            <th>Location</th>
            <th>Account status</th>
            <th>Settings</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="member in merchantRows" :key="member.id">
            <td>
              <div class="admin-agent-cell">
                <span>{{ initial(member.restaurant_name) }}</span>
                <div>
                  <strong>{{ member.restaurant_name }}</strong>
                  <small>Joined {{ member.created_at_format }}</small>
                  <span v-if="requiresReview(member)" class="merchant-application-status" :class="'merchant-application-status-' + reviewStatus(member)">
                    {{ reviewStatus(member).replace(/_/g, ' ') }}
                  </span>
                  <a v-if="member.facebook && member.accout_type" :href="member.facebook" target="_blank" rel="noopener" class="dashboard-inline-link">
                    {{ member.accout_type.title }} <i class="fas fa-external-link-alt"></i>
                  </a>
                </div>
              </div>
            </td>
            <td>
              <strong>{{ member.email || 'No email address' }}</strong>
              <small>{{ contactNumbers(member) }}</small>
            </td>
            <td>
              <strong>{{ member.city || 'City unavailable' }}</strong>
              <small class="dashboard-clamp">{{ member.address || 'No address provided' }}</small>
              <small v-if="member.latitude || member.longtitude">{{ member.latitude || '—' }}, {{ member.longtitude || '—' }}</small>
            </td>
            <td>
              <strong>{{ member.active ? 'Active' : 'Inactive' }}</strong>
              <small>{{ member.isverified ? 'Verified' : 'Not verified' }}</small>
            </td>
            <td>
              <a :href="`/data/dashboard/merchant/${member.id}/settings`" class="merchant-settings-link"><i class="fas fa-cog"></i><span>Manage settings</span><span aria-hidden="true">→</span></a>
            </td>
            <td>
              <div class="dashboard-actions">
                <a v-if="requiresReview(member)" :href="reviewUrl(member)" class="dashboard-action-primary"><i class="fas fa-clipboard-check"></i><span>Review application</span></a>
                <a v-if="member.user_id" :href="'/merchant/aulogin/' + member.user_id" class="dashboard-action-secondary" title="Open merchant dashboard">
                  <i class="fas fa-external-link-alt"></i><span>Open</span>
                </a>
                <button v-if="member.user_id" type="button" class="dashboard-action-secondary" :disabled="sendingId === member.id" title="Email merchant login" @click="sendMerchantLogin(member.id)">
                  <i :class="sendingId === member.id ? 'fas fa-circle-notch fa-spin' : 'far fa-envelope'"></i><span>Send login</span>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else class="admin-empty-state">
      <span><i class="fas fa-store"></i></span>
      <h3>No merchant partners found</h3>
      <p>{{ emptyStateMessage }}</p>
    </div>

    <admin-pagination v-if="!loading" :pagination="members" @pagination-change-page="fetchData" />
  </div>
</template>

<script>
export default {
  data() {
    return {
      members: { data: [], current_page: 1, last_page: 1, total: 0 },
      loading: true,
      search: '',
      activeTab: 'all',
      counts: { all: 0, store_open: 0, inactive: 0 },
      searchTimer: null,
      sendingId: null,
    };
  },
  computed: {
    tabs() {
      return [
        { value: 'all', label: 'All Accounts', badgeClass: 'badge-primary' },
        { value: 'store_open', label: 'Store Open', badgeClass: 'badge-success' },
        { value: 'inactive', label: 'Inactive', badgeClass: 'badge-secondary' },
      ];
    },
    merchantRows() {
      return this.members.data || [];
    },
    emptyStateMessage() {
      if (this.search) {
        return 'Try a different search term or account tab.';
      }

      if (this.activeTab === 'store_open') {
        return 'No merchant stores are currently open.';
      }

      if (this.activeTab === 'inactive') {
        return 'No merchant accounts are waiting for activation.';
      }

      return 'Merchant partners will appear here after registration.';
    },
  },
  watch: {
    search() {
      window.clearTimeout(this.searchTimer);
      this.searchTimer = window.setTimeout(() => this.fetchData(1), 300);
    },
  },
  mounted() {
    this.fetchData();
  },
  beforeDestroy() {
    window.clearTimeout(this.searchTimer);
  },
  methods: {
    needsInitialReview(member) {
      return member.application_source === 'merchant_registration' && member.recruitment_status !== 'approved';
    },
    reviewStatus(member) {
      return this.needsInitialReview(member)
        ? (member.recruitment_status || 'pending')
        : (member.application_status || 'pending_review');
    },
    reviewUrl(member) {
      return this.needsInitialReview(member)
        ? `/data/dashboard/merchant-applications/${member.id}`
        : `/data/dashboard/merchant/${member.id}/application`;
    },
    requiresReview(member) {
      return Boolean(member.agent_id) || member.application_source === 'merchant_registration';
    },
    fetchData(page = 1) {
      this.loading = true;
      const query = new URLSearchParams({ api_token, page, search: this.search, status: this.activeTab });

      axios.get(`/api/data/merchant/search/list?${query.toString()}`)
        .then((response) => {
          this.members = response.data.members;
          this.counts = response.data.counts || this.counts;
        })
        .catch(() => toastr.error('Unable to load merchant partners.'))
        .finally(() => {
          this.loading = false;
        });
    },
    selectTab(tab) {
      if (this.activeTab === tab) {
        return;
      }

      this.activeTab = tab;
      this.fetchData(1);
    },
    sendMerchantLogin(memberId) {
      this.sendingId = memberId;
      axios.post(`/api/data/merchant/${memberId}/password/submit?api_token=${api_token}`)
        .then((response) => {
          response.data.status ? toastr.success(response.data.message) : toastr.info(response.data.message);
        })
        .catch(() => toastr.error('Unable to send the merchant login email.'))
        .finally(() => {
          this.sendingId = null;
        });
    },
    initial(value) {
      return value ? value.charAt(0).toUpperCase() : 'M';
    },
    contactNumbers(member) {
      return [member.mobile, member.telephone].filter(Boolean).join(' • ') || 'No phone number';
    },
    pluralize(word, count) {
      return count === 1 ? word : `${word}s`;
    },
  },
};
</script>

<style scoped>
.merchant-settings-link {
  align-items: center;
  color: #42494c;
  display: inline-flex;
  font-size: 11px;
  font-weight: 700;
  gap: 7px;
  padding: 5px 0;
  text-decoration: none;
}
.merchant-settings-link i { color: #858c8f; }
.merchant-settings-link:hover,
.merchant-settings-link:focus { color: #20272b; text-decoration: underline; }
</style>
