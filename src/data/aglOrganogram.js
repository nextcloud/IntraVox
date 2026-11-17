export const AGL_ORGANOGRAM = Object.freeze({
  title: 'Athena Group | Executive & Functional Matrix',
  executives: [
    { name: 'Scott Mackenzie', role: 'Group CEO' },
    { name: 'Victor Kovacs', role: 'Group COO' },
    { name: 'Alfredo Carrasco', role: 'Group CFO' },
    { name: 'Stelios Xeroudakis', role: 'Founder & Group CTO' },
  ],
  companies: ['Athena Group Ltd', 'Cloud Carib Ltd', 'Athena Cloud Ltd'],
  columns: [
    {
      title: 'Office of the COO',
      pageId: 'page-sovereign-commercial',
      groups: [
        { title: 'Sales & Marketing', children: ['Inside & Outside Sales', 'Client Success', 'Marketing', 'Public Sector'] },
        { title: 'Solutions & Sales Ops', children: ['Solutions', 'Sales Ops', 'Commercial Delivery'] },
        { title: 'Administration', children: ['Business Services', 'HR'] },
      ],
    },
    {
      title: 'Client Services & Engineering Matrix',
      pageId: 'page-sovereign-engineering',
      subtitle: 'COO Delivery · Acting CTO Technical · Acting CEO Operational',
      groups: [
        { title: 'Caribbean Hub', pageId: 'page-a1000002-0002-4000-8000-000000000021', children: ['Nassau C3'] },
        { title: 'EMEA Hub', pageId: 'page-a1000002-0002-4000-8000-000000000022', children: ['Athens C3'] },
      ],
    },
    {
      title: 'Office of the CFO',
      pageId: 'page-sovereign-finance',
      groups: [
        { title: 'Finance', children: ['AP', 'AR', 'Controller'] },
        { title: 'Treasury' },
        { title: 'Budget & Forecasting' },
      ],
    },
    {
      title: 'Office of the CTO',
      pageId: 'page-sovereign-technology',
      groups: [
        { title: 'Information Systems & Development', children: ['ISD'] },
        { title: 'Product / Services' },
      ],
    },
    {
      title: 'Office of the CEO',
      pageId: 'page-sovereign-governance',
      groups: [
        { title: 'General Counsel', children: ['Contracts'] },
        { title: 'Corporate Security / CISO', children: ['Compliance'] },
      ],
    },
  ],
});

export const createOrganogramWidget = () => ({
  type: 'organogram',
  title: AGL_ORGANOGRAM.title,
  defaultExpanded: true,
  showCompanies: true,
});
