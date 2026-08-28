<template>
  <div class="card admin-card dashboard-data-card">
    <div class="admin-card-header">
      <div><h2>All members</h2><p>{{ members.total || 0 }} registered customer accounts</p></div>
      <label class="admin-search dashboard-search">
        <i class="fas fa-search"></i>
        <input v-model="search" type="search" placeholder="Search name, email, or mobile" aria-label="Search members">
        <button v-if="search" type="button" aria-label="Clear search" @click="search = ''">&times;</button>
      </label>
    </div>

    <div v-if="loading" class="dashboard-loading"><i class="fas fa-circle-notch fa-spin"></i> Loading members…</div>
    <div v-else-if="memberRows.length" class="table-responsive">
      <table class="table dashboard-data-table dashboard-member-table">
        <thead><tr><th>Member</th><th>Contact</th><th>Date joined</th></tr></thead>
        <tbody>
          <tr v-for="member in memberRows" :key="member.id">
            <td><div class="admin-agent-cell"><span>{{ initial(member) }}</span><div><strong>{{ fullName(member) }}</strong><small>Member #{{ member.id }}</small></div></div></td>
            <td><strong>{{ member.email }}</strong><small>{{ member.mobile || 'No mobile number' }}</small></td>
            <td><strong>{{ member.created_at_format }}</strong></td>
          </tr>
        </tbody>
      </table>
    </div>
    <div v-else class="admin-empty-state"><span><i class="fas fa-users"></i></span><h3>No members found</h3><p>{{ search ? 'Try a different search term.' : 'Registered customer accounts will appear here.' }}</p></div>
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
    };
  },
  computed: {
    memberRows() {
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
      axios.get(`/api/data/member/search/list?${query.toString()}`)
        .then((response) => {
          this.members = response.data.members;
        })
        .catch(() => toastr.error('Unable to load members.'))
        .finally(() => {
          this.loading = false;
        });
    },
    fullName(member) {
      return [member.firstname, member.lastname].filter(Boolean).join(' ') || 'Unnamed member';
    },
    initial(member) {
      return this.fullName(member).charAt(0).toUpperCase();
    },
  },
};
</script>
