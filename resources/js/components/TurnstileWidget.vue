<template>
  <div v-if="siteKey" class="turnstile-field">
    <div ref="widget"></div>
    <p v-if="errorMessage" class="turnstile-error" role="alert">{{ errorMessage }}</p>
  </div>
</template>

<script>
const scriptUrl = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let loader;

function loadTurnstile() {
  if (window.turnstile) {
    return Promise.resolve(window.turnstile);
  }

  if (loader) {
    return loader;
  }

  loader = new Promise((resolve, reject) => {
    let script = document.querySelector('script[src^="https://challenges.cloudflare.com/turnstile/v0/api.js"]');

    const loaded = () => window.turnstile
      ? resolve(window.turnstile)
      : reject(new Error('Turnstile did not initialize.'));

    if (!script) {
      script = document.createElement('script');
      script.src = scriptUrl;
      script.defer = true;
      document.head.appendChild(script);
    }

    script.addEventListener('load', loaded, { once: true });
    script.addEventListener('error', () => reject(new Error('Turnstile could not be loaded.')), { once: true });
  });

  return loader;
}

export default {
  props: {
    action: {
      type: String,
      required: true,
    },
    value: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      errorMessage: '',
      widgetId: null,
    };
  },
  computed: {
    siteKey() {
      const meta = document.querySelector('meta[name="turnstile-site-key"]');

      return meta ? meta.content : '';
    },
  },
  mounted() {
    if (!this.siteKey) {
      return;
    }

    loadTurnstile()
      .then((turnstile) => {
        this.widgetId = turnstile.render(this.$refs.widget, {
          sitekey: this.siteKey,
          action: this.action,
          theme: 'auto',
          size: 'flexible',
          callback: (token) => {
            this.errorMessage = '';
            this.$emit('input', token);
          },
          'expired-callback': () => {
            this.$emit('input', '');
            this.errorMessage = 'Security verification expired. Please try again.';
          },
          'error-callback': () => {
            this.$emit('input', '');
            this.errorMessage = 'Security verification could not be completed. Please try again.';
          },
        });
      })
      .catch(() => {
        this.errorMessage = 'Security verification could not be loaded. Please refresh the page.';
      });
  },
  beforeDestroy() {
    if (this.widgetId !== null && window.turnstile) {
      window.turnstile.remove(this.widgetId);
    }
  },
  methods: {
    reset() {
      this.$emit('input', '');

      if (this.widgetId !== null && window.turnstile) {
        window.turnstile.reset(this.widgetId);
      }
    },
  },
};
</script>

<style scoped>
.turnstile-field {
  margin: 1rem 0;
}

.turnstile-error {
  color: #b42318;
  font-size: 0.875rem;
  margin: 0.5rem 0 0;
}
</style>
