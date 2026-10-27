<template>
    <span v-if="count" class="badge badge-danger" aria-live="polite"
        >{{ count }} unread</span
    >
</template>
<script>
import axios from "axios";
export default {
    data: () => ({ count: 0 }),
    mounted() {
        this.poll();
        this.timer = setInterval(this.poll, 30000);
    },
    beforeDestroy() {
        clearInterval(this.timer);
    },
    methods: {
        async poll() {
            if (document.hidden) return;
            try {
                this.count = (
                    await axios.get("/data/dashboard/support/alerts")
                ).data.count;
            } catch (_) {}
        },
    },
};
</script>
