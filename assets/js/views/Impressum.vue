<template>
  <b-container fluid role="main" tag="main">
    <h1 class="mb-4">Impressum</h1>
    <div class="d-inline-flex">
      <table class="table table-sm">
        <tbody>
        <tr>
          <th></th>
          <td>Pierre Schmitz</td>
        </tr>
        <tr>
          <th>Address</th>
          <td>Saarstr. 1<br/>
            53175 Bonn<br/>
            Germany
          </td>
        </tr>
        <tr>
          <th>Phone</th>
          <td>+49 228 9716608</td>
        </tr>
        <tr>
          <th>E-Mail</th>
          <td><a href="mailto:pierre@archlinux.de">pierre@archlinux.de</a></td>
        </tr>
        </tbody>
      </table>
    </div>
  </b-container>
</template>

<script>
export default {
  name: 'Impressum',
  metaInfo: {
    title: 'Impressum',
    meta: [{ vmid: 'robots', name: 'robots', content: 'noindex' }]
  }
}
</script>
