## Usage

To use this component simply wrap any other component which may throw an Error. Errors thrown in child components will automatically bubble up to the `ErrorBoundary` component.

```html
<ErrorBoundary>
  <ImUnstable />
</ErrorBoundary>
```

## Props

| Attribute        | Description                                                 | Type       | Required | Default         |
| ---------------- | ----------------------------------------------------------- | ---------- | -------- | --------------- |
| fall-back        | Fallback component to render in case of error.              | Component  | `false`  | DefaultFallback |
| on-error         | Callback function to perform on error.                      | `Function` | `false`  | `null`          |
| params           | Props to pass to your fall back component.                  | `Object`   | `false`  | `{}`            |
| stop-propagation | Stop propagation of errors to other `errorCaptured` hooks.  | `Boolean`  | `false`  | `false`         |
| tag              | Wrapper tag used if multiple elements are passed to a slot. | `String`   | `false`  | `span`          |

## Scoped Slots

| Property | Description                                 | Type      |
| -------- | ------------------------------------------- | --------- |
| err      | The error                                   | `Error`   |
| hasError | Whether an error occurred.                  | `Boolean` |
| info     | Information on where the error was captured | `String`  |

## How to Use

### Fallback UI via fall-back

We can provide a fallback UI to display via the `fall-back` prop. It simply takes a Vue component to render.

#### Basic Example

```html
<template>
  <ErrorBoundary :fall-back="productError">
    <ProductCard ... />
  </ErrorBoundary>
</template>

<script>
  import ProductErrorCard from "...";

  export default {
    // ...
    data() {
      return {
        productError: ProductErrorCard,
      };
    },
  };
</script>
```
