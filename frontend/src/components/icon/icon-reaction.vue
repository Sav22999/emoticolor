<script setup lang="ts">
import { onMounted } from 'vue'
import reaction1f3af from '@/assets/reactions/1f3af.svg?component'
import reaction1f3b1 from '@/assets/reactions/1f3b1.svg?component'
import reaction1f3b5 from '@/assets/reactions/1f3b5.svg?component'
import reaction1f3c5 from '@/assets/reactions/1f3c5.svg?component'
import reaction1f3c6 from '@/assets/reactions/1f3c6.svg?component'
import reaction1f3d6 from '@/assets/reactions/1f3d6.svg?component'
import reaction1f3f4 from '@/assets/reactions/1f3f4.svg?component'
import reaction1f4a1 from '@/assets/reactions/1f4a1.svg?component'
import reaction1f4a3 from '@/assets/reactions/1f4a3.svg?component'
import reaction1f4a4 from '@/assets/reactions/1f4a4.svg?component'
import reaction1f4a5 from '@/assets/reactions/1f4a5.svg?component'
import reaction1f4a6 from '@/assets/reactions/1f4a6.svg?component'
import reaction1f4a9 from '@/assets/reactions/1f4a9.svg?component'
import reaction1f4aa from '@/assets/reactions/1f4aa.svg?component'
import reaction1f4ab from '@/assets/reactions/1f4ab.svg?component'
import reaction1f4ac from '@/assets/reactions/1f4ac.svg?component'
import reaction1f4ad from '@/assets/reactions/1f4ad.svg?component'
import reaction1f4b2 from '@/assets/reactions/1f4b2.svg?component'
import reaction1f4b8 from '@/assets/reactions/1f4b8.svg?component'
import reaction1f4cc from '@/assets/reactions/1f4cc.svg?component'
import reaction1f4da from '@/assets/reactions/1f4da.svg?component'
import reaction1f4e8 from '@/assets/reactions/1f4e8.svg?component'
import reaction1f4f7 from '@/assets/reactions/1f4f7.svg?component'
import reaction1f5a4 from '@/assets/reactions/1f5a4.svg?component'
import reaction1f5e1 from '@/assets/reactions/1f5e1.svg?component'
import reaction1f5e3 from '@/assets/reactions/1f5e3.svg?component'
import reaction1f6a9 from '@/assets/reactions/1f6a9.svg?component'
import reaction1f6b9 from '@/assets/reactions/1f6b9.svg?component'
import reaction1f6ba from '@/assets/reactions/1f6ba.svg?component'
import reaction1f6bc from '@/assets/reactions/1f6bc.svg?component'
import reaction1f7e5 from '@/assets/reactions/1f7e5.svg?component'
import reaction1f7e6 from '@/assets/reactions/1f7e6.svg?component'
import reaction1f7e7 from '@/assets/reactions/1f7e7.svg?component'
import reaction1f7e8 from '@/assets/reactions/1f7e8.svg?component'
import reaction1f7e9 from '@/assets/reactions/1f7e9.svg?component'
import reaction1f7ea from '@/assets/reactions/1f7ea.svg?component'
import reaction1f7eb from '@/assets/reactions/1f7eb.svg?component'
import reaction1f9b5 from '@/assets/reactions/1f9b5.svg?component'
import reaction1f9b6 from '@/assets/reactions/1f9b6.svg?component'
import reaction1f9d0 from '@/assets/reactions/1f9d0.svg?component'
import reaction1f9e1 from '@/assets/reactions/1f9e1.svg?component'
import reaction1f31e from '@/assets/reactions/1f31e.svg?component'
import reaction1f31f from '@/assets/reactions/1f31f.svg?component'
import reaction1f38c from '@/assets/reactions/1f38c.svg?component'
import reaction1f44a from '@/assets/reactions/1f44a.svg?component'
import reaction1f44b from '@/assets/reactions/1f44b.svg?component'
import reaction1f44c from '@/assets/reactions/1f44c.svg?component'
import reaction1f44d from '@/assets/reactions/1f44d.svg?component'
import reaction1f44e from '@/assets/reactions/1f44e.svg?component'
import reaction1f44f from '@/assets/reactions/1f44f.svg?component'
import reaction1f47d from '@/assets/reactions/1f47d.svg?component'
import reaction1f47f from '@/assets/reactions/1f47f.svg?component'
import reaction1f48b from '@/assets/reactions/1f48b.svg?component'
import reaction1f48e from '@/assets/reactions/1f48e.svg?component'
import reaction1f49a from '@/assets/reactions/1f49a.svg?component'
import reaction1f49b from '@/assets/reactions/1f49b.svg?component'
import reaction1f49c from '@/assets/reactions/1f49c.svg?component'
import reaction1f49d from '@/assets/reactions/1f49d.svg?component'
import reaction1f60a from '@/assets/reactions/1f60a.svg?component'
import reaction1f60b from '@/assets/reactions/1f60b.svg?component'
import reaction1f60d from '@/assets/reactions/1f60d.svg?component'
import reaction1f60e from '@/assets/reactions/1f60e.svg?component'
import reaction1f61a from '@/assets/reactions/1f61a.svg?component'
import reaction1f61b from '@/assets/reactions/1f61b.svg?component'
import reaction1f61c from '@/assets/reactions/1f61c.svg?component'
import reaction1f61f from '@/assets/reactions/1f61f.svg?component'
import reaction1f62a from '@/assets/reactions/1f62a.svg?component'
import reaction1f62c from '@/assets/reactions/1f62c.svg?component'
import reaction1f62f from '@/assets/reactions/1f62f.svg?component'
import reaction1f64c from '@/assets/reactions/1f64c.svg?component'
import reaction1f64f from '@/assets/reactions/1f64f.svg?component'
import reaction1f90c from '@/assets/reactions/1f90c.svg?component'
import reaction1f90f from '@/assets/reactions/1f90f.svg?component'
import reaction1f91a from '@/assets/reactions/1f91a.svg?component'
import reaction1f91b from '@/assets/reactions/1f91b.svg?component'
import reaction1f91c from '@/assets/reactions/1f91c.svg?component'
import reaction1f91d from '@/assets/reactions/1f91d.svg?component'
import reaction1f91e from '@/assets/reactions/1f91e.svg?component'
import reaction1f91f from '@/assets/reactions/1f91f.svg?component'
import reaction1f92b from '@/assets/reactions/1f92b.svg?component'
import reaction1f92c from '@/assets/reactions/1f92c.svg?component'
import reaction1f92d from '@/assets/reactions/1f92d.svg?component'
import reaction1f92e from '@/assets/reactions/1f92e.svg?component'
import reaction1f92f from '@/assets/reactions/1f92f.svg?component'
import reaction1f310 from '@/assets/reactions/1f310.svg?component'
import reaction1f381 from '@/assets/reactions/1f381.svg?component'
import reaction1f385 from '@/assets/reactions/1f385.svg?component'
import reaction1f442 from '@/assets/reactions/1f442.svg?component'
import reaction1f443 from '@/assets/reactions/1f443.svg?component'
import reaction1f446 from '@/assets/reactions/1f446.svg?component'
import reaction1f447 from '@/assets/reactions/1f447.svg?component'
import reaction1f448 from '@/assets/reactions/1f448.svg?component'
import reaction1f449 from '@/assets/reactions/1f449.svg?component'
import reaction1f450 from '@/assets/reactions/1f450.svg?component'
import reaction1f451 from '@/assets/reactions/1f451.svg?component'
import reaction1f484 from '@/assets/reactions/1f484.svg?component'
import reaction1f493 from '@/assets/reactions/1f493.svg?component'
import reaction1f498 from '@/assets/reactions/1f498.svg?component'
import reaction1f499 from '@/assets/reactions/1f499.svg?component'
import reaction1f514 from '@/assets/reactions/1f514.svg?component'
import reaction1f515 from '@/assets/reactions/1f515.svg?component'
import reaction1f525 from '@/assets/reactions/1f525.svg?component'
import reaction1f600 from '@/assets/reactions/1f600.svg?component'
import reaction1f601 from '@/assets/reactions/1f601.svg?component'
import reaction1f602 from '@/assets/reactions/1f602.svg?component'
import reaction1f605 from '@/assets/reactions/1f605.svg?component'
import reaction1f607 from '@/assets/reactions/1f607.svg?component'
import reaction1f608 from '@/assets/reactions/1f608.svg?component'
import reaction1f618 from '@/assets/reactions/1f618.svg?component'
import reaction1f621 from '@/assets/reactions/1f621.svg?component'
import reaction1f622 from '@/assets/reactions/1f622.svg?component'
import reaction1f624 from '@/assets/reactions/1f624.svg?component'
import reaction1f633 from '@/assets/reactions/1f633.svg?component'
import reaction1f634 from '@/assets/reactions/1f634.svg?component'
import reaction1f635_200d_1f4ab from '@/assets/reactions/1f635-200d-1f4ab.svg?component'
import reaction1f637 from '@/assets/reactions/1f637.svg?component'
import reaction1f642 from '@/assets/reactions/1f642.svg?component'
import reaction1f643 from '@/assets/reactions/1f643.svg?component'
import reaction1f919 from '@/assets/reactions/1f919.svg?component'
import reaction1f922 from '@/assets/reactions/1f922.svg?component'
import reaction1f924 from '@/assets/reactions/1f924.svg?component'
import reaction1f925 from '@/assets/reactions/1f925.svg?component'
import reaction1f927 from '@/assets/reactions/1f927.svg?component'
import reaction1f929 from '@/assets/reactions/1f929.svg?component'
import reaction1f947 from '@/assets/reactions/1f947.svg?component'
import reaction1f948 from '@/assets/reactions/1f948.svg?component'
import reaction1f949 from '@/assets/reactions/1f949.svg?component'
import reaction1f970 from '@/assets/reactions/1f970.svg?component'
import reaction1f971 from '@/assets/reactions/1f971.svg?component'
import reaction1f973 from '@/assets/reactions/1f973.svg?component'
import reaction1faf3 from '@/assets/reactions/1faf3.svg?component'
import reaction1faf5 from '@/assets/reactions/1faf5.svg?component'
import reaction1faf6 from '@/assets/reactions/1faf6.svg?component'
import reaction2764 from '@/assets/reactions/2764.svg?component'
import reaction2764_fe0f_200d_1f525 from '@/assets/reactions/2764-fe0f-200d-1f525.svg?component'
import type { ReactionType } from '@/utils/types.ts'

const props = withDefaults(
  defineProps<{
    name: ReactionType
    size?: '16px' | '18px' | '20px' | '24px'
  }>(),
  {
    name: '',
    size: '18px',
  },
)

onMounted(() => {})
</script>

<template>
  <div
    v-if="name !== ''"
    class="icon"
    :style="{
      width: props.size,
      height: props.size,
      'min-width': props.size,
      'min-height': props.size,
    }"
  >
    <reaction1f3af v-if="name === '1f3af'"></reaction1f3af>
    <reaction1f3b1 v-if="name === '1f3b1'"></reaction1f3b1>
    <reaction1f3b5 v-if="name === '1f3b5'"></reaction1f3b5>
    <reaction1f3c5 v-if="name === '1f3c5'"></reaction1f3c5>
    <reaction1f3c6 v-if="name === '1f3c6'"></reaction1f3c6>
    <reaction1f3d6 v-if="name === '1f3d6'"></reaction1f3d6>
    <reaction1f3f4 v-if="name === '1f3f4'"></reaction1f3f4>
    <reaction1f4a1 v-if="name === '1f4a1'"></reaction1f4a1>
    <reaction1f4a3 v-if="name === '1f4a3'"></reaction1f4a3>
    <reaction1f4a4 v-if="name === '1f4a4'"></reaction1f4a4>
    <reaction1f4a5 v-if="name === '1f4a5'"></reaction1f4a5>
    <reaction1f4a6 v-if="name === '1f4a6'"></reaction1f4a6>
    <reaction1f4a9 v-if="name === '1f4a9'"></reaction1f4a9>
    <reaction1f4aa v-if="name === '1f4aa'"></reaction1f4aa>
    <reaction1f4ab v-if="name === '1f4ab'"></reaction1f4ab>
    <reaction1f4ac v-if="name === '1f4ac'"></reaction1f4ac>
    <reaction1f4ad v-if="name === '1f4ad'"></reaction1f4ad>
    <reaction1f4b2 v-if="name === '1f4b2'"></reaction1f4b2>
    <reaction1f4b8 v-if="name === '1f4b8'"></reaction1f4b8>
    <reaction1f4cc v-if="name === '1f4cc'"></reaction1f4cc>
    <reaction1f4da v-if="name === '1f4da'"></reaction1f4da>
    <reaction1f4e8 v-if="name === '1f4e8'"></reaction1f4e8>
    <reaction1f4f7 v-if="name === '1f4f7'"></reaction1f4f7>
    <reaction1f5a4 v-if="name === '1f5a4'"></reaction1f5a4>
    <reaction1f5e1 v-if="name === '1f5e1'"></reaction1f5e1>
    <reaction1f5e3 v-if="name === '1f5e3'"></reaction1f5e3>
    <reaction1f6a9 v-if="name === '1f6a9'"></reaction1f6a9>
    <reaction1f6b9 v-if="name === '1f6b9'"></reaction1f6b9>
    <reaction1f6ba v-if="name === '1f6ba'"></reaction1f6ba>
    <reaction1f6bc v-if="name === '1f6bc'"></reaction1f6bc>
    <reaction1f7e5 v-if="name === '1f7e5'"></reaction1f7e5>
    <reaction1f7e6 v-if="name === '1f7e6'"></reaction1f7e6>
    <reaction1f7e7 v-if="name === '1f7e7'"></reaction1f7e7>
    <reaction1f7e8 v-if="name === '1f7e8'"></reaction1f7e8>
    <reaction1f7e9 v-if="name === '1f7e9'"></reaction1f7e9>
    <reaction1f7ea v-if="name === '1f7ea'"></reaction1f7ea>
    <reaction1f7eb v-if="name === '1f7eb'"></reaction1f7eb>
    <reaction1f9b5 v-if="name === '1f9b5'"></reaction1f9b5>
    <reaction1f9b6 v-if="name === '1f9b6'"></reaction1f9b6>
    <reaction1f9d0 v-if="name === '1f9d0'"></reaction1f9d0>
    <reaction1f9e1 v-if="name === '1f9e1'"></reaction1f9e1>
    <reaction1f31e v-if="name === '1f31e'"></reaction1f31e>
    <reaction1f31f v-if="name === '1f31f'"></reaction1f31f>
    <reaction1f38c v-if="name === '1f38c'"></reaction1f38c>
    <reaction1f44a v-if="name === '1f44a'"></reaction1f44a>
    <reaction1f44b v-if="name === '1f44b'"></reaction1f44b>
    <reaction1f44c v-if="name === '1f44c'"></reaction1f44c>
    <reaction1f44d v-if="name === '1f44d'"></reaction1f44d>
    <reaction1f44e v-if="name === '1f44e'"></reaction1f44e>
    <reaction1f44f v-if="name === '1f44f'"></reaction1f44f>
    <reaction1f47d v-if="name === '1f47d'"></reaction1f47d>
    <reaction1f47f v-if="name === '1f47f'"></reaction1f47f>
    <reaction1f48b v-if="name === '1f48b'"></reaction1f48b>
    <reaction1f48e v-if="name === '1f48e'"></reaction1f48e>
    <reaction1f49a v-if="name === '1f49a'"></reaction1f49a>
    <reaction1f49b v-if="name === '1f49b'"></reaction1f49b>
    <reaction1f49c v-if="name === '1f49c'"></reaction1f49c>
    <reaction1f49d v-if="name === '1f49d'"></reaction1f49d>
    <reaction1f60a v-if="name === '1f60a'"></reaction1f60a>
    <reaction1f60b v-if="name === '1f60b'"></reaction1f60b>
    <reaction1f60d v-if="name === '1f60d'"></reaction1f60d>
    <reaction1f60e v-if="name === '1f60e'"></reaction1f60e>
    <reaction1f61a v-if="name === '1f61a'"></reaction1f61a>
    <reaction1f61b v-if="name === '1f61b'"></reaction1f61b>
    <reaction1f61c v-if="name === '1f61c'"></reaction1f61c>
    <reaction1f61f v-if="name === '1f61f'"></reaction1f61f>
    <reaction1f62a v-if="name === '1f62a'"></reaction1f62a>
    <reaction1f62c v-if="name === '1f62c'"></reaction1f62c>
    <reaction1f62f v-if="name === '1f62f'"></reaction1f62f>
    <reaction1f64c v-if="name === '1f64c'"></reaction1f64c>
    <reaction1f64f v-if="name === '1f64f'"></reaction1f64f>
    <reaction1f90c v-if="name === '1f90c'"></reaction1f90c>
    <reaction1f90f v-if="name === '1f90f'"></reaction1f90f>
    <reaction1f91a v-if="name === '1f91a'"></reaction1f91a>
    <reaction1f91b v-if="name === '1f91b'"></reaction1f91b>
    <reaction1f91c v-if="name === '1f91c'"></reaction1f91c>
    <reaction1f91d v-if="name === '1f91d'"></reaction1f91d>
    <reaction1f91e v-if="name === '1f91e'"></reaction1f91e>
    <reaction1f91f v-if="name === '1f91f'"></reaction1f91f>
    <reaction1f92b v-if="name === '1f92b'"></reaction1f92b>
    <reaction1f92c v-if="name === '1f92c'"></reaction1f92c>
    <reaction1f92d v-if="name === '1f92d'"></reaction1f92d>
    <reaction1f92e v-if="name === '1f92e'"></reaction1f92e>
    <reaction1f92f v-if="name === '1f92f'"></reaction1f92f>
    <reaction1f310 v-if="name === '1f310'"></reaction1f310>
    <reaction1f381 v-if="name === '1f381'"></reaction1f381>
    <reaction1f385 v-if="name === '1f385'"></reaction1f385>
    <reaction1f442 v-if="name === '1f442'"></reaction1f442>
    <reaction1f443 v-if="name === '1f443'"></reaction1f443>
    <reaction1f446 v-if="name === '1f446'"></reaction1f446>
    <reaction1f447 v-if="name === '1f447'"></reaction1f447>
    <reaction1f448 v-if="name === '1f448'"></reaction1f448>
    <reaction1f449 v-if="name === '1f449'"></reaction1f449>
    <reaction1f450 v-if="name === '1f450'"></reaction1f450>
    <reaction1f451 v-if="name === '1f451'"></reaction1f451>
    <reaction1f484 v-if="name === '1f484'"></reaction1f484>
    <reaction1f493 v-if="name === '1f493'"></reaction1f493>
    <reaction1f498 v-if="name === '1f498'"></reaction1f498>
    <reaction1f499 v-if="name === '1f499'"></reaction1f499>
    <reaction1f514 v-if="name === '1f514'"></reaction1f514>
    <reaction1f515 v-if="name === '1f515'"></reaction1f515>
    <reaction1f525 v-if="name === '1f525'"></reaction1f525>
    <reaction1f600 v-if="name === '1f600'"></reaction1f600>
    <reaction1f601 v-if="name === '1f601'"></reaction1f601>
    <reaction1f602 v-if="name === '1f602'"></reaction1f602>
    <reaction1f605 v-if="name === '1f605'"></reaction1f605>
    <reaction1f607 v-if="name === '1f607'"></reaction1f607>
    <reaction1f608 v-if="name === '1f608'"></reaction1f608>
    <reaction1f618 v-if="name === '1f618'"></reaction1f618>
    <reaction1f621 v-if="name === '1f621'"></reaction1f621>
    <reaction1f622 v-if="name === '1f622'"></reaction1f622>
    <reaction1f624 v-if="name === '1f624'"></reaction1f624>
    <reaction1f633 v-if="name === '1f633'"></reaction1f633>
    <reaction1f634 v-if="name === '1f634'"></reaction1f634>
    <reaction1f635_200d_1f4ab v-if="name === '1f635-200d-1f4ab'"></reaction1f635_200d_1f4ab>
    <reaction1f637 v-if="name === '1f637'"></reaction1f637>
    <reaction1f642 v-if="name === '1f642'"></reaction1f642>
    <reaction1f643 v-if="name === '1f643'"></reaction1f643>
    <reaction1f919 v-if="name === '1f919'"></reaction1f919>
    <reaction1f922 v-if="name === '1f922'"></reaction1f922>
    <reaction1f924 v-if="name === '1f924'"></reaction1f924>
    <reaction1f925 v-if="name === '1f925'"></reaction1f925>
    <reaction1f927 v-if="name === '1f927'"></reaction1f927>
    <reaction1f929 v-if="name === '1f929'"></reaction1f929>
    <reaction1f947 v-if="name === '1f947'"></reaction1f947>
    <reaction1f948 v-if="name === '1f948'"></reaction1f948>
    <reaction1f949 v-if="name === '1f949'"></reaction1f949>
    <reaction1f970 v-if="name === '1f970'"></reaction1f970>
    <reaction1f971 v-if="name === '1f971'"></reaction1f971>
    <reaction1f973 v-if="name === '1f973'"></reaction1f973>
    <reaction1faf3 v-if="name === '1faf3'"></reaction1faf3>
    <reaction1faf5 v-if="name === '1faf5'"></reaction1faf5>
    <reaction1faf6 v-if="name === '1faf6'"></reaction1faf6>
    <reaction2764 v-if="name === '2764'"></reaction2764>
    <reaction2764_fe0f_200d_1f525
      v-if="name === '2764-fe0f-200d-1f525'"
    ></reaction2764_fe0f_200d_1f525>
  </div>
</template>

<style scoped lang="scss">
.icon {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: var(--no-spacing);

  > * {
    width: 100%;
    height: 100%;
  }
}
</style>
