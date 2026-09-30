# Runtime customisations

## 2026-09-30: Compact highlighted Tree navigation on mobile

Restore the LTS arrangement below 40rem in the custom mod-navigation module:
a one-third-width thumbnail beside the heading, with the description spanning
both columns underneath. Reduce mobile description text to 14px and tighten
item and child-link spacing while retaining all links and at least 24px link
height. Items without images use the full width. Desktop, standard Tree and
other navigation formats retain their existing layout. This is module-scoped
SCSS only; no database migration or JavaScript is required.

## 2026-09-29: Image container queries

Navigation cards request a 1024px maximum instead of 400px so ComponentLibrary
generates container-query variants rather than an empty list that leaves only
the low-resolution placeholder visible. ImageContainerQueries uses the
ComponentLibrary/Component/Image/ContainerQueryData filter to scale landscape
and portrait thresholds site-wide to two thirds, selecting larger variants earlier.
This targets approximately 1.5x density rather than the initial 2x adjustment,
balancing sharpness and transfer size. A 387px landscape card selects the 768px
variant instead of 1024px. Transfers may still increase on 1x displays.
Available source resolution and each caller's maximum size still apply.

## 2026-09-29: Disable downstream event review registrations

Comment out the three SubsiteImportReview handler registrations in App.php now
that events are reviewed at the source. Keep their implementation for possible
reuse. Remove the interim site-option switch; no runtime toggle is introduced.
Existing event statuses and External Content source settings remain unchanged.
