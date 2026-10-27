<template>
  <div v-if="pagination && pagination.total" class="dashboard-pagination">
    <span class="dashboard-pagination-summary">
      Showing {{ pagination.from || 0 }}–{{ pagination.to || 0 }} of {{ pagination.total }}
    </span>
    <nav v-if="pagination.last_page > 1" class="dashboard-pagination-buttons" aria-label="Table pagination">
      <button type="button" :disabled="pagination.current_page <= 1" @click="changePage(pagination.current_page - 1)">
        <i class="fas fa-chevron-left"></i><span class="sr-only">Previous page</span>
      </button>
      <button
        v-for="page in pages"
        :key="page"
        type="button"
        :class="{ active: page === pagination.current_page }"
        :aria-current="page === pagination.current_page ? 'page' : null"
        @click="changePage(page)"
      >{{ page }}</button>
      <button type="button" :disabled="pagination.current_page >= pagination.last_page" @click="changePage(pagination.current_page + 1)">
        <i class="fas fa-chevron-right"></i><span class="sr-only">Next page</span>
      </button>
    </nav>
  </div>
</template>

<script>
export default {
  props: {
    pagination: {
      type: Object,
      required: true,
    },
  },
  computed: {
    pages() {
      const start = Math.max(1, this.pagination.current_page - 2);
      const end = Math.min(this.pagination.last_page, this.pagination.current_page + 2);

      return Array.from({ length: end - start + 1 }, (_, index) => start + index);
    },
  },
  methods: {
    changePage(page) {
      if (page < 1 || page > this.pagination.last_page || page === this.pagination.current_page) {
        return;
      }

      this.$emit('pagination-change-page', page);
    },
  },
};
</script>
