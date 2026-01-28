<script setup lang="ts">
import topbar from '@/components/header/topbar.vue'
import InputGeneric from '@/components/input/input-generic.vue'
import InputPassword from '@/components/input/input-password.vue'
import ButtonGeneric from '@/components/button/button-generic.vue'
import separator from '@/components/separator.vue'
import textLink from '@/components/text/text-link.vue'
import TextParagraph from '@/components/text/text-paragraph.vue'
import router from '@/router'
import TextInfo from '@/components/text/text-info.vue'
import InputCheckbox from '@/components/input/input-checkbox.vue'
import InputChip from '@/components/input/input-chip.vue'
import InputSearchbox from '@/components/input/input-searchbox.vue'

function doAction(name: string) {
  console.log('Action:', name)
}

function openLogin() {
  router.push({ name: 'login' })
}

function doClick() {
  console.log('Login Clicked')
}
</script>

<template>
  <topbar variant="simple-big" @action="doAction($event)" title="Nuovo account"></topbar>
  <main>
    <div class="content">
      <text-paragraph align="start">
        Compila tutti i campi per creare un nuovo account.
      </text-paragraph>
      <div class="container">
        <div class="textboxes">
          <input-generic
            icon="email"
            @input="doAction($event)"
            placeholder="indirizzo email"
            chars-disallowed=" "
          ></input-generic>
          <div class="info-box">
            <input-password
              @input="doAction($event)"
              placeholder="password"
              :min-length="10"
              chars-disallowed=" "
            ></input-password>
            <text-info>
              deve essere almeno di 10 caratteri e deve contenere almeno una lettera maiuscola, una
              lettera minuscola e un numero
            </text-info>
          </div>
          <input-password
            @input="doAction($event)"
            placeholder="ripeti password"
            chars-disallowed=" "
          ></input-password>
          <div class="info-box">
            <input-generic
              @input="doAction($event)"
              placeholder="username"
              icon="username"
              chars-allowed="abcdefghijklmnopqrstuvwxyz."
              :min-length="5"
              :max-length="20"
            ></input-generic>
            <text-info>
              sarà visibile a tutti gli utenti. Deve avere una lunghezza compresa tra 5 e 20
              caratteri, e può contenere solo lettere (minuscole), numeri e il punto
            </text-info>
            <input-searchbox
              @input="doAction($event)"
              placeholder="interessi"
              chars-allowed="abcdefghijklmnopqrstuvwxyz "
              :min-length="3"
              :max-length="30"
              :show-search-icon="true"
            ></input-searchbox>
          </div>
        </div>
        <div class="buttons">
          <button-generic
            @action="doAction('read-privacy')"
            icon="external"
            variant="primary"
            text="Leggi l'Informativa Privacy per proseguire"
            align="center"
            iconPosition="end"
            :disabled="false"
          />
          <ButtonGeneric
            @action="doAction('continue')"
            icon="forward"
            variant="cta"
            text="Prosegui"
            align="center"
            iconPosition="end"
            :disabled="true"
          />
        </div>
      </div>
      <separator variant="primary" />
      <text-link text="Hai già un account? Accedi" @action="openLogin" />
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
    gap: var(--spacing-32);

    .textboxes {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-16);

      > .info-box {
        display: flex;
        flex-direction: column;
        gap: var(--spacing-4);
      }
    }

    .buttons {
      display: flex;
      flex-direction: column;
      gap: var(--spacing-16);
    }
  }
}
</style>
