<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import InputGeneric from '@/components/input/input-generic.vue'
import InputPassword from '@/components/input/input-password.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import separator from '@/components/separator.vue'
import textLink from '@/components/text/text-link.vue'

function doAction(name: string) {
  console.log('Action:', name)
}

function doClick() {}
</script>

<template>
  <topbar variant="simple-big" @action="doAction($event)" title="Accedi"></topbar>
  <main>
    <div class="content">
      <div class="container">
        <div class="textboxes">
          <input-generic
            icon="email"
            @input="doAction($event)"
            placeholder="indirizzo email"
          ></input-generic>
          <input-password @input="doAction($event)" placeholder="password"></input-password>
        </div>
        <button-generic
          @action="doClick"
          icon="login"
          variant="cta"
          text="Accedi"
          align="center"
          iconPosition="end"
          :disabled="false"
        />
      </div>
      <text-link text="Hai dimenticato la password?" @action="doAction('link')" />
      <separator variant="primary" />
      <ButtonGeneric
        @action="doClick"
        icon="plus-circle"
        variant="primary"
        text="Crea un account"
        align="center"
        iconPosition="end"
        :disabled="false"
      />
    </div>
  </main>
</template>

<style scoped lang="scss">
.fullwidth {
  width: 100%;
  display: flex;
  justify-content: start;
}
.content {
  display: flex;
  flex-direction: column;
  padding: var(--padding-32);
  gap: var(--spacing-16);
  height: auto;

  .container {
    display: flex;
    flex-direction: column;
    gap: var(--padding-32);

    .textboxes {
      display: flex;
      flex-direction: column;
      gap: var(--padding-16);
    }
  }
}
</style>
