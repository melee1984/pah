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

    <div v-if="loading" class="dashboard-loading"><i class="fas fa-circle-notch fa-spin"></i> Loading merchant partners…</div>

    <div v-else-if="merchantRows.length" class="table-responsive">
      <table class="table dashboard-data-table dashboard-merchant-table">
        <thead>
          <tr>
            <th>Merchant partner</th>
            <th>Contact</th>
            <th>Location</th>
            <th>Availability</th>
            <th>Capabilities</th>
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
              <div class="dashboard-toggle-list">
                <label>
                  <span>Account active</span>
                  <input v-model="member.active" type="checkbox" :disabled="isBusy(member, 'active')" @change="toggleSetting(member, 'active', 'status')">
                  <i></i>
                </label>
                <label>
                  <span>Store open</span>
                  <input v-model="member.istoreopen" type="checkbox" :disabled="isBusy(member, 'istoreopen')" @change="toggleSetting(member, 'istoreopen', 'online')">
                  <i></i>
                </label>
              </div>
            </td>
            <td>
              <div class="dashboard-toggle-list">
                <label>
                  <span>Verified</span>
                  <input v-model="member.isverified" type="checkbox" :disabled="isBusy(member, 'isverified')" @change="toggleSetting(member, 'isverified', 'verify')">
                  <i></i>
                </label>
                <label>
                  <span>Pre-order</span>
                  <input v-model="member.is_pre_order" type="checkbox" :disabled="isBusy(member, 'is_pre_order')" @change="toggleSetting(member, 'is_pre_order', 'preorder')">
                  <i></i>
                </label>
                <label>
                  <span>Comm. discount</span>
                  <input v-model="member.pahatud_comm_discount" type="checkbox" :disabled="isBusy(member, 'pahatud_comm_discount')" @change="toggleSetting(member, 'pahatud_comm_discount', 'coomrate')">
                  <i></i>
                </label>
              </div>
            </td>
            <td>
              <div class="dashboard-actions">
                <a :href="'/merchant/aulogin/' + member.user_id" class="dashboard-action-primary" title="Open merchant dashboard">
                  <i class="fas fa-external-link-alt"></i><span>Open</span>
                </a>
                <button type="button" class="dashboard-action-secondary" :disabled="sendingId === member.id" title="Email merchant login" @click="sendMerchantLogin(member.id)">
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
      <p>{{ search ? 'Try a different search term.' : 'Merchant partners will appear here after registration.' }}</p>
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
      searchTimer: null,
      busy: {},
      sendingId: null,
    };
  },
  computed: {
    merchantRows() {
      return this.members.data || [];
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
    fetchData(page = 1) {
      this.loading = true;
      const query = new URLSearchParams({ api_token, page, search: this.search });

      axios.get(`/api/data/merchant/search/list?${query.toString()}`)
        .then((response) => {
          this.members = response.data.members;
        })
        .catch(() => toastr.error('Unable to load merchant partners.'))
        .finally(() => {
          this.loading = false;
        });
    },
    toggleSetting(member, field, endpoint) {
      const key = `${member.id}:${field}`;
      this.$set(this.busy, key, true);

      axios.post(`/api/data/merchant/${member.id}/${endpoint}/submit?api_token=${api_token}`)
        .then((response) => {
          if (response.data.status) {
            toastr.success(response.data.message);
            return;
          }

          member[field] = !member[field];
          toastr.info(response.data.message);
        })
        .catch(() => {
          member[field] = !member[field];
          toastr.error('Unable to update this merchant setting.');
        })
        .finally(() => this.$delete(this.busy, key));
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
    isBusy(member, field) {
      return Boolean(this.busy[`${member.id}:${field}`]);
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
