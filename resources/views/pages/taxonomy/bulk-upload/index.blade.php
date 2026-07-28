@extends('layouts.app')

@php
    $textarea =
        'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 min-h-[32rem] w-full rounded-lg border border-gray-300 bg-transparent px-4 py-3 font-mono text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400';
    $exampleJson = <<<'JSON'
{
  "categories": [
    {
      "name": "Doctor",
      "subcategories": [
        {
          "name": "General Physician",
          "skills": [
            "Medical Diagnosis",
            "Patient Consultation",
            "Prescription Writing",
            "Disease Prevention",
            "Health Assessment"
          ]
        },
        {
          "name": "Cardiologist",
          "skills": [
            "ECG Interpretation",
            "Heart Disease Diagnosis",
            "Echocardiography",
            "Cardiac Risk Assessment",
            "Hypertension Management"
          ]
        },
        {
          "name": "Dermatologist",
          "skills": [
            "Skin Disease Diagnosis",
            "Acne Treatment",
            "Skin Biopsy",
            "Cosmetic Dermatology",
            "Laser Therapy"
          ]
        },
        {
          "name": "Pediatrician",
          "skills": [
            "Child Healthcare",
            "Vaccination",
            "Growth Monitoring",
            "Neonatal Care",
            "Parental Counseling"
          ]
        },
        {
          "name": "Gynecologist",
          "skills": [
            "Prenatal Care",
            "Women's Health",
            "Ultrasound Interpretation",
            "Family Planning",
            "Obstetric Care"
          ]
        }
      ]
    },
    {
      "name": "Lawyer",
      "subcategories": [
        {
          "name": "Corporate Lawyer",
          "skills": [
            "Contract Drafting",
            "Corporate Compliance",
            "Business Law",
            "Mergers & Acquisitions",
            "Legal Advisory"
          ]
        },
        {
          "name": "Criminal Lawyer",
          "skills": [
            "Criminal Defense",
            "Court Representation",
            "Case Investigation",
            "Bail Applications",
            "Trial Advocacy"
          ]
        },
        {
          "name": "Family Lawyer",
          "skills": [
            "Divorce Cases",
            "Child Custody",
            "Marriage Registration",
            "Family Mediation",
            "Domestic Violence Cases"
          ]
        },
        {
          "name": "Immigration Lawyer",
          "skills": [
            "Visa Applications",
            "Immigration Appeals",
            "Work Permit",
            "Citizenship Applications",
            "Refugee Law"
          ]
        },
        {
          "name": "Tax Lawyer",
          "skills": [
            "Tax Compliance",
            "Tax Planning",
            "Corporate Tax",
            "VAT Advisory",
            "Tax Dispute Resolution"
          ]
        }
      ]
    },
    {
      "name": "Study Abroad Expert",
      "subcategories": [
        {
          "name": "Admission Consultant",
          "skills": [
            "University Selection",
            "Application Review",
            "Admission Strategy",
            "Deadline Management",
            "Profile Evaluation"
          ]
        },
        {
          "name": "Visa Consultant",
          "skills": [
            "Student Visa Processing",
            "Document Verification",
            "Visa Interview Preparation",
            "Financial Documentation",
            "Visa Compliance"
          ]
        },
        {
          "name": "Scholarship Advisor",
          "skills": [
            "Scholarship Search",
            "Funding Strategy",
            "Scholarship Applications",
            "Essay Review",
            "Eligibility Assessment"
          ]
        },
        {
          "name": "SOP & Essay Consultant",
          "skills": [
            "Statement of Purpose Writing",
            "Personal Statement Review",
            "Essay Editing",
            "CV Review",
            "LOR Guidance"
          ]
        },
        {
          "name": "IELTS Advisor",
          "skills": [
            "Speaking Preparation",
            "Writing Evaluation",
            "Reading Strategies",
            "Listening Practice",
            "Mock Tests"
          ]
        }
      ]
    }
  ]
}
JSON;
@endphp

@section('content')
    <div x-data="{
        exampleJson: @js($exampleJson),
        useExample() {
            this.$refs.jsonInput.value = this.exampleJson;
        }
    }">
        <x-common.page-breadcrumb pageTitle="Bulk upload" />

        <div class="mb-6">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Paste taxonomy JSON on the left. Existing category, subcategory, and skill names are skipped.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-common.component-card title="Upload JSON">
                @if ($errors->any())
                    <div
                        class="mb-4 rounded-lg border border-error-200 bg-error-50 px-4 py-3 text-sm text-error-700 dark:border-error-500/30 dark:bg-error-500/10 dark:text-error-400">
                        <ul class="list-disc space-y-1 pl-4">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('taxonomy.bulk-upload.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="json" class="{{ $label }}">JSON payload</label>
                        <textarea id="json" name="json" x-ref="jsonInput" class="{{ $textarea }}"
                            placeholder="Paste your taxonomy JSON here..."
                            required>{{ old('json') }}</textarea>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-brand-500 px-5 py-3 text-sm font-medium text-white hover:bg-brand-600">
                            Import taxonomy
                        </button>
                        <button type="button" @click="useExample()"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-3 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-white/[0.03]">
                            Use example
                        </button>
                    </div>
                </form>
            </x-common.component-card>

            <x-common.component-card title="Example JSON">
                <p class="mb-3 text-sm text-gray-500 dark:text-gray-400">
                    Expected shape: categories → subcategories → skills
                </p>
                <pre
                    class="max-h-[32rem] overflow-auto rounded-lg border border-gray-200 bg-gray-50 p-4 font-mono text-xs leading-5 text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90"><code>{{ $exampleJson }}</code></pre>
            </x-common.component-card>
        </div>
    </div>
@endsection
