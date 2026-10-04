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

## 2026-10-05: Table layout experiment

Add TableLayout's ComponentLibrary view override for a native `Visa större`
button with a decorative fullscreen icon, available at all viewport widths.
The copied Table template otherwise matches upstream; compare it when updating
ComponentLibrary. This is a runtime customisation, not a database migration.

Local table SCSS removes heading truncation and duplicate icon spacing, reduces
horizontal cell padding and removes the generic minimum column width. Layout JS
prefers one-line headings, tries wrapping only if the table overflows, and keeps
wrapping only when the table fits and every heading uses at most two lines.
Otherwise it restores one-line headings and horizontal scroll, including on
desktop. ResizeObserver responds to container width (including opening modals);
font loading and row changes also trigger remeasurement. Multidimensional tables
keep their upstream collapse behaviour and skip the wrapping experiment.
Scroll indicators and empty footers are hidden when the table fits; scrollable
containers support keyboard scrolling. Upstream sorting/filtering/modal code is
retained. No vendor files were edited.

Verified on Sommarjobb för unga: default desktop container 809px fits all six
columns on one line; 580px viewport / 530px container wraps longer headings onto
two lines without scrolling; 390px viewport / 356px container uses horizontal
scroll and one-line headings. Tested keyboard scrolling, Enter to open the modal,
Escape to close, and focus returning to the trigger. Site build and PHP syntax
checks passed. General improvements can later move to ComponentLibrary/Styleguide.

### Follow-up: use upstream spacing variables

Set `--c-table--inset-padding-x` rather than overriding cell padding. Keep
upstream ownership of vertical content padding and header/footer padding; the
new modal trigger and control gaps consume the same component spacing aliases.
Button colours consume table colour tokens. Structural heading fixes remain
explicit because no upstream variables exist for truncation or heading spacing.

### Follow-up: article spacing and modal outer width

Reset margin-top on the component's own table so `.c-article table` does not
insert a gap above its headings, both inline and inside the modal. Ordinary
article tables retain their existing spacing. The upstream table panel has
100% width plus margins; subtract twice the gutter from its width, using a
local gutter alias derived from the same table space token as upstream.
Keep this width correction scoped to table panels.
